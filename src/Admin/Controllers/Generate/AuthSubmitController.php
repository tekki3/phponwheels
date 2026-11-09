<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers\Generate;

use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Console\AuthMaker;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;
use RuntimeException;

final class AuthSubmitController implements Handler
{
    public function __construct(private readonly TemplateEngine $view, private readonly ProjectPaths $paths, private readonly Session $session)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        $files = null;
        $error = null;
        try {
            $files = (new AuthMaker($this->paths->root))->create();
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return $this->view->respond('generate/auth', [
            'title' => 'Generate authentication', 'subtitle' => 'Login, verified registration, MFA and user management.',
            'currentPath' => '/generate/auth', 'csrfToken' => Csrf::token($this->session), 'files' => $files, 'error' => $error,
            'wiring' => AuthMaker::WIRING,
        ], $error === null ? Status::Ok : Status::UnprocessableEntity);
    }
}
