<?php

declare(strict_types=1);

namespace App\Controllers;

use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\Oidc\OidcProviders;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;
use PhpOnWheels\View\TemplateEngine;

final class LoginController implements Handler
{
    public function __construct(
        private readonly TemplateEngine $view,
        private readonly Session $session,
        private readonly Authenticator $auth,
        private readonly OidcProviders $providers,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/notes');
        }

        return $this->view->respond('login', [
            'title' => 'Sign in',
            // Lets the layout mark the active navigation item.
            'currentPath' => '/login',
            'csrfToken' => Csrf::token($this->session),
            'error' => $this->session->takeFlash('error'),
            'notice' => $this->session->takeFlash('notice'),
            'oldEmail' => $this->session->takeFlash('old_email') ?? '',
            'providers' => $this->providers->names(),
        ])->withHeader('Cache-Control', 'no-store');
    }
}
