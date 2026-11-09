<?php

declare(strict_types=1);

namespace App\AuthKit\Controllers;

use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class LogoutController implements Handler
{
    public function __construct(private readonly Authenticator $auth)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/login')->withHeader('Cache-Control', 'no-store');
    }
}
