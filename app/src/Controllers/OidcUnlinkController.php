<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AccountSecurity;
use App\Support\AuthPage;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\Oidc\ExternalAccounts;
use PhpOnWheels\Auth\SecurityState;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class OidcUnlinkController implements Handler
{
    public function __construct(
        private readonly AccountSecurity $security, private readonly ExternalAccounts $accounts,
        private readonly SecurityState $state, private readonly Authenticator $auth, private readonly AuthPage $page,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $user = $this->security->confirm($request);
            $this->accounts->unlink($user->authIdentifier(), (int) ($request->attribute('id') ?? '0'));
            $this->state->revoke($user->authIdentifier());
            $this->auth->login($user);
        } catch (AuthenticationFailed $exception) {
            return $this->page->back('/account/security', $exception->getMessage(), true);
        }

        return $this->page->back('/account/security', 'External account unlinked. Other sessions must sign in again.');
    }
}
