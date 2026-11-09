<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Config\Environment;
use PhpOnWheels\Crypto\Key;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

/**
 * The same as `pow key:generate`: prints a key rather than writing one, so
 * generating one here never touches .env either. Nothing stops a developer
 * from clicking this and not using the result — that is exactly the point.
 */
final class GenerateKeyController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Environment $env,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('tools', [
            'title' => 'Tools',
            'subtitle' => 'pow key:generate and pow mail:test, without leaving the browser.',
            'currentPath' => '/tools',
            'csrfToken' => Csrf::token($this->session),
            'mailDriver' => $this->env->string('MAIL_DRIVER', 'array'),
            'defaultFrom' => $this->env->raw('MAIL_FROM_ADDRESS') ?? '',
            'generatedKey' => Key::generate()->exportForConfiguration(),
            'mailTo' => '',
            'mailFrom' => '',
            'mailResult' => null,
            'error' => null,
        ]);
    }
}
