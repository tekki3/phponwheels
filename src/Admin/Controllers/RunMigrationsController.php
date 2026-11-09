<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Database\MigrationFailed;
use PhpOnWheels\Database\Migrator;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

final class RunMigrationsController implements Handler
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $applied = $this->migrator->migrate();
        } catch (MigrationFailed $e) {
            $this->session->flash('admin.error', $e->getMessage());

            return Response::redirect('/migrations');
        }

        $this->session->flash(
            'admin.notice',
            $applied === [] ? 'Nothing to migrate.' : sprintf('Applied %d migration%s.', count($applied), count($applied) === 1 ? '' : 's'),
        );

        return Response::redirect('/migrations');
    }
}
