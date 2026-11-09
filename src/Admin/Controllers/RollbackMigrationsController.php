<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Database\IrreversibleMigration;
use PhpOnWheels\Database\MigrationFailed;
use PhpOnWheels\Database\Migrator;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

/**
 * Reverses the most recent batch — one deployment's worth of changes, the
 * same unit `pow migrate:rollback` operates on.
 */
final class RollbackMigrationsController implements Handler
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        // Same default as pow migrate:rollback --batches=1; anything less
        // than 1 would roll back nothing while claiming to, so it is clamped
        // rather than passed through.
        $batches = max(1, (int) ($request->form('batches') ?? '1'));

        try {
            $reversed = $this->migrator->rollback($batches);
        } catch (IrreversibleMigration | MigrationFailed $e) {
            $this->session->flash('admin.error', $e->getMessage());

            return Response::redirect('/migrations');
        }

        $this->session->flash(
            'admin.notice',
            $reversed === [] ? 'Nothing to roll back.' : sprintf('Reversed %d migration%s.', count($reversed), count($reversed) === 1 ? '' : 's'),
        );

        return Response::redirect('/migrations');
    }
}
