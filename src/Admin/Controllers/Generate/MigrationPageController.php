<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class MigrationPageController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->view->respond('generate/migration', [
            'title' => 'Generate a migration',
            'subtitle' => 'Writes a file into database/migrations — the same as pow make:migration.',
            'currentPath' => '/generate/migration',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => '', 'table' => '', 'sequential' => false],
            'result' => null,
            'error' => null,
        ]);
    }
}
