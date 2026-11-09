<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class SeedersController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $directory = $this->paths->root . '/database/seeders';
        $error = null;
        $seeders = [];

        if (is_dir($directory) && !is_readable($directory)) {
            $error = sprintf('Seeder directory "%s" is not readable.', $directory);
        } elseif (is_dir($directory)) {
            $files = scandir($directory);

            if ($files === false) {
                $error = sprintf('Could not list seeders in "%s".', $directory);
            } else {
                $seeders = array_values(array_map(
                    static fn (string $file): string => basename($file, '.php'),
                    array_filter($files, static fn (string $file): bool => str_ends_with($file, '.php')),
                ));
            }
        }

        return $this->view->respond('seeders', [
            'title' => 'Seeders',
            'subtitle' => 'Run this project’s database seeders in filename order, or run one by name.',
            'currentPath' => '/seeders',
            'csrfToken' => Csrf::token($this->session),
            'seeders' => $seeders,
            'flash' => $this->session->takeFlash('admin.notice'),
            'error' => $this->session->takeFlash('admin.error') ?? $error,
        ]);
    }
}
