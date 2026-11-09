<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Database\Connection;
use PhpOnWheels\Database\SeedFailed;
use PhpOnWheels\Database\SeedRunner;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

final class RunSeedersController implements Handler
{
    public function __construct(
        private readonly Connection $database,
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $name = trim($request->form('name') ?? '');

        try {
            $seeded = (new SeedRunner(
                $this->database,
                $this->paths->root . '/database/seeders',
            ))->run($name === '' ? null : $name);
        } catch (SeedFailed $e) {
            $this->session->flash('admin.error', $e->getMessage());

            return Response::redirect('/seeders');
        }

        $this->session->flash(
            'admin.notice',
            $seeded === [] ? 'Nothing to seed.' : sprintf('Seeded: %s.', implode(', ', $seeded)),
        );

        return Response::redirect('/seeders');
    }
}
