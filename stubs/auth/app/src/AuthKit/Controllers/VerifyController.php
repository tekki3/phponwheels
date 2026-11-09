<?php

declare(strict_types=1);

namespace App\AuthKit\Controllers;

use App\AuthKit\AuthPage;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Security\Csrf;
use PhpOnWheels\Session\Session;

final class VerifyController implements Handler
{
    public function __construct(private readonly AuthPage $page, private readonly Session $session)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        $token = $request->uri->queryParam('token');
        if ($token !== null) {
            $this->session->regenerate();
            Csrf::rotate($this->session);
            $this->session->set('_registration_token', preg_match('/^[a-f0-9]{64}$/D', $token) === 1 ? $token : '');

            return Response::redirect('/register/verify')->withHeader('Cache-Control', 'no-store');
        }

        return $this->page->render('verify', 'Verify your email');
    }
}
