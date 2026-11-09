<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

/**
 * Signs the user out.
 *
 * A POST, not a GET: a link that logs someone out can be triggered from
 * anywhere, and CSRF protection only applies to state-changing methods.
 */
final class LogoutController implements Handler
{
    public function __construct(
        private readonly Authenticator $auth,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/login');
    }
}
