<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class ControllerPageController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/controller', [
            'title' => 'Generate a controller',
            'subtitle' => 'Writes a controller, and optionally its template — the same as pow make:controller.',
            'currentPath' => '/generate/controller',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => '', 'withView' => false],
            'result' => null,
            'error' => null,
        ]);
    }
}
