<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\ErrorRenderer;

use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Twig\Environment;

/**
 * Provides the ability to render custom Twig-based HTML error pages
 * in non-debug mode, otherwise falls back to HtmlErrorRenderer.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class TwigErrorRenderer implements ErrorRendererInterface
{
    private $twig;
    private $fallbackErrorRenderer;
    private $debug;

    public function __construct(Environment $twig, HtmlErrorRenderer $fallbackErrorRenderer = null, bool $debug = false)
    {
        $this->twig = $twig;
        $this->fallbackErrorRenderer = $fallbackErrorRenderer ?? new HtmlErrorRenderer();
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Throwable $exception): FlattenException
    {
        $exception = $this->fallbackErrorRenderer->render($exception);

        if ($this->debug || !$template = $this->findTemplate($exception->getStatusCode())) {
            return $exception;
        }

        return $exception->setAsString($this->twig->render($template, [
            'exception' => $exception,
            'status_code' => $exception->getStatusCode(),
            'status_text' => $exception->getStatusText(),
        ]));
    }

    private function findTemplate(int $statusCode): ?string
    {
        $template = sprintf('@Twig/Exception/error%s.html.twig', $statusCode);
        if ($this->twig->getLoader()->exists($template)) {
            return $template;
        }

        $template = '@Twig/Exception/error.html.twig';
        if ($this->twig->getLoader()->exists($template)) {
            return $template;
        }

        return null;
    }
}