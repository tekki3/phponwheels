<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Console\FormFieldSpec;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class FormPageController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/form', [
            'title' => 'Generate a form',
            'subtitle' => 'Writes a form definition, and optionally its two pages — the same as pow make:form.',
            'currentPath' => '/generate/form',
            'csrfToken' => Csrf::token($this->session),
            'availableTypes' => implode(', ', FormFieldSpec::available()),
            'old' => [
                'name' => '',
                'fields' => 'name:text,email:email,message:textarea',
                'captcha' => false,
                'honeypot' => true,
                'controllers' => true,
            ],
            'result' => null,
            'routesBlock' => '',
            'error' => null,
        ]);
    }
}
