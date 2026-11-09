<?php

declare(strict_types=1);

namespace App\AuthKit\Controllers;

use App\AuthKit\AuthPage;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\Oidc\OidcProviders;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class LoginController implements Handler
{
    public function __construct(private readonly AuthPage $page, private readonly Authenticator $auth, private readonly OidcProviders $providers)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->auth->check() ? Response::redirect('/account')
            : $this->page->render('login', 'Sign in', ['providers' => $this->providers->names()]);
    }
}
