<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use InvalidArgumentException;
use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\SeedMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

final class SeedSubmitController implements Handler
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
        $force = $request->form('force') === '1';

        try {
            $result = (new SeedMaker($this->paths->root))->create($name, force: $force);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->view->respond('generate/seed', [
                'title' => 'Generate a seeder',
                'subtitle' => 'Writes a database seeder, the same as pow make:seed.',
                'currentPath' => '/generate/seed',
                'csrfToken' => Csrf::token($this->session),
                'old' => ['name' => $name],
                'result' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        return $this->view->respond('generate/seed', [
            'title' => 'Generate a seeder',
            'subtitle' => 'Writes a database seeder, the same as pow make:seed.',
            'currentPath' => '/generate/seed',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => ''],
            'result' => $result,
            'error' => null,
        ]);
    }
}
