<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Database\Migrator;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class MigrationsController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Migrator $migrator,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $batches = $this->migrator->batches();
        $pending = $this->migrator->pending();

        $rows = [];
        foreach ($this->migrator->available() as $name) {
            $rows[] = [
                'name' => $name,
                'applied' => isset($batches[$name]),
                'batch' => $batches[$name] ?? null,
            ];
        }

        return $this->view->respond('migrations', [
            'title' => 'Migrations',
            'subtitle' => 'Every file in database/migrations, applied or pending, with its batch.',
            'currentPath' => '/migrations',
            'csrfToken' => Csrf::token($this->session),
            'rows' => $rows,
            'pendingCount' => count($pending),
            'flash' => $this->session->takeFlash('admin.notice'),
            'error' => $this->session->takeFlash('admin.error'),
        ]);
    }
}
