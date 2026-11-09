<?php

declare(strict_types=1);

namespace App\AuthKit\Controllers;

use App\AuthKit\AuthPage;
use App\AuthKit\Registration;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

final class VerifySubmitController implements Handler
{
    public function __construct(private readonly AuthPage $page, private readonly Registration $registration, private readonly Session $session)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        $token = $this->session->get('_registration_token') ?? '';
        $this->session->remove('_registration_token');
        try {
            $this->registration->verify($token);
        } catch (AuthenticationFailed $exception) {
            return $this->page->back('/register', $exception->getMessage(), true);
        }

        return $this->page->back('/login', 'Email verified. You can now sign in.');
    }
}
