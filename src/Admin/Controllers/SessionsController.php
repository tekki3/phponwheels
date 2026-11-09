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

final class SessionsController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $files = glob($this->paths->root . '/storage/sessions/sess_*') ?: [];

        return $this->view->respond('sessions', [
            'title' => 'Sessions',
            'subtitle' => 'Session files on disk, in storage/sessions.',
            'currentPath' => '/sessions',
            'csrfToken' => Csrf::token($this->session),
            'fileCount' => count($files),
            // Own session included deliberately: it is one more file in the
            // same pool, the same as any visitor's, and sessions:gc treats it
            // no differently.
            'flash' => $this->session->takeFlash('admin.notice'),
            'error' => $this->session->takeFlash('admin.error'),
        ]);
    }
}
