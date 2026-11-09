<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Config\Environment;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class ToolsController implements Handler
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
            'generatedKey' => null,
            'mailTo' => '',
            'mailFrom' => '',
            'mailResult' => null,
            'error' => null,
        ]);
    }
}
