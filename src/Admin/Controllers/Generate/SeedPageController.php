<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class SeedPageController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/seed', [
            'title' => 'Generate a seeder',
            'subtitle' => 'Writes a database seeder, the same as pow make:seed.',
            'currentPath' => '/generate/seed',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => ''],
            'result' => null,
            'error' => null,
        ]);
    }
}
