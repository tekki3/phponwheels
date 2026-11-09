<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AuthPage;
use InvalidArgumentException;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Auth\PasswordReset;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;
use PhpOnWheels\Session\Session;

final class PasswordUpdateController implements Handler
{
    public function __construct(private readonly PasswordReset $reset, private readonly Session $session, private readonly AuthPage $page)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $password = $request->form('password') ?? '';
            if ($password !== $request->form('password_confirmation')) {
                throw new InvalidArgumentException('The passwords do not match.');
            }
            $this->reset->reset($this->session->get('_reset_token') ?? '', $password);
        } catch (AuthenticationFailed | InvalidArgumentException $exception) {
            return $this->page->back('/password/reset', $exception->getMessage(), true);
        }
        $this->session->remove('_reset_token');
        $this->session->remove('_mfa_user');
        $this->session->remove('_auth_id');

        return $this->page->back('/login', 'Password changed. Sign in again; your authenticator is still required if enabled.');
    }
}
