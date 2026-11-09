<?php

declare(strict_types=1);

namespace PhpOnWheels\Admin\Controllers;

use PhpOnWheels\Admin\ProjectPaths;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\FileSessionStore;
use PhpOnWheels\Session\Session;

final class GcSessionsController implements Handler
{
    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly Session $session,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        // A fresh store rather than an injected one: collectGarbage() is a
        // one-off filesystem sweep, not something worth a container
        // registration for the one route that calls it.
        $removed = (new FileSessionStore($this->paths->root . '/storage/sessions'))->collectGarbage();

        $this->session->flash(
            'admin.notice',
            sprintf('Removed %d expired session%s.', $removed, $removed === 1 ? '' : 's'),
        );

        return Response::redirect('/sessions');
    }
}
