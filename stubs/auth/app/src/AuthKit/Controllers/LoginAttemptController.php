<?php

declare(strict_types=1);

namespace App\AuthKit\Controllers;

use App\AuthKit\AuthPage;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\LoginFlow;
use PhpOnWheels\Auth\LoginThrottle;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class LoginAttemptController implements Handler
{
    public function __construct(
        private readonly AuthPage $page, private readonly Authenticator $auth,
        private readonly LoginFlow $flow, private readonly LoginThrottle $throttle,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $email = mb_strtolower(trim($request->form('email') ?? ''));
        $key = 'password:' . $email;
        if ($this->throttle->tooManyAttempts($key)) {
            return $this->page->back('/login', 'Too many attempts. Try again later.', true);
        }
        $this->throttle->record($key);
        $user = $this->auth->validateCredentials($email, $request->form('password') ?? '');
        if ($user === null) {
            return $this->page->back('/login', 'Unable to sign in. Check your credentials and verify your email.', true);
        }
        $this->throttle->clear($key);
        $this->flow->begin($user);

        return $this->flow->pending() ? Response::redirect('/mfa') : $this->page->signedIn();
    }
}
