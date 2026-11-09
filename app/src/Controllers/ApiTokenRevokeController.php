<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AccountSecurity;
use App\Support\AuthPage;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class ApiTokenRevokeController implements Handler
{
    public function __construct(
        private readonly Tokens $tokens, private readonly AccountSecurity $security, private readonly AuthPage $page,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $user = $this->security->confirm($request);
            $this->tokens->revoke($user->authIdentifier(), $request->attribute('id') ?? '');
        } catch (AuthenticationFailed $error) {
            return $this->page->back('/account/tokens', $error->getMessage(), true);
        }

        return $this->page->back('/account/tokens', 'API token revoked.');
    }
}
