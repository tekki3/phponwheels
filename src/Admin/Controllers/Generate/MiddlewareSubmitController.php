<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use InvalidArgumentException;
use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\MiddlewareMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

final class MiddlewareSubmitController implements Handler
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
            $result = (new MiddlewareMaker($this->paths->root))->create($name, $force);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->view->respond('generate/middleware', [
                'title' => 'Generate middleware',
                'subtitle' => 'Writes a pass-through Middleware class — the same as pow make:middleware.',
                'currentPath' => '/generate/middleware',
                'csrfToken' => Csrf::token($this->session),
                'old' => ['name' => $name],
                'result' => null,
                'error' => $e->getMessage(),
            ], Status::UnprocessableEntity);
        }

        return $this->view->respond('generate/middleware', [
            'title' => 'Generate middleware',
            'subtitle' => 'Writes a pass-through Middleware class — the same as pow make:middleware.',
            'currentPath' => '/generate/middleware',
            'csrfToken' => Csrf::token($this->session),
            'old' => ['name' => ''],
            'result' => $result,
            'error' => null,
        ]);
    }
}
