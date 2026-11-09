<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use InvalidArgumentException;
use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\MigrationMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

final class MigrationSubmitController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $name = trim($request->form('name') ?? '');
        $table = trim($request->form('table') ?? '');
        $sequential = $request->form('sequential') === '1';
        $force = $request->form('force') === '1';

        $old = ['name' => $name, 'table' => $table, 'sequential' => $sequential];

        try {
            $result = (new MigrationMaker($this->paths->root))->create(
                $name,
                $table === '' ? null : $table,
                $sequential,
                $force,
            );
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->view->respond('generate/migration', [
                'title' => 'Generate a migration',
                'subtitle' => 'Writes a file into database/migrations — the same as pow make:migration.',
                'currentPath' => '/generate/migration',
                'csrfToken' => Csrf::token($this->session),
                'old' => $old,
                'result' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        return $this->view->respond('generate/migration', [
            'title' => 'Generate a migration',
            'subtitle' => 'Writes a file into database/migrations — the same as pow make:migration.',
            'currentPath' => '/generate/migration',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => '', 'table' => '', 'sequential' => $sequential],
            'result' => $result,
            'error' => null,
        ]);
    }
}
