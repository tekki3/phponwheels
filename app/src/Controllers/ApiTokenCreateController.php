<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AccountSecurity;
use App\Support\AuthPage;
use InvalidArgumentException;
use PhpOnWheels\Api\ApiError;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Auth\Mfa;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class ApiTokenCreateController implements Handler
{
    public function __construct(
        private readonly Tokens $tokens, private readonly AccountSecurity $security,
        private readonly Mfa $mfa, private readonly AuthPage $page,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            $user = $this->security->confirm($request);
            $token = $this->tokens->personal($user, $request->form('name') ?? '', ['profile:read']);
        } catch (AuthenticationFailed | InvalidArgumentException | ApiError $error) {
            return $this->page->back('/account/tokens', $error->getMessage(), true);
        }

        return $this->page->render('auth/tokens', 'API token created', [
            'tokens' => $this->tokens->listed($user->authIdentifier()), 'issuedToken' => $token->value,
            'enabled' => $this->mfa->enabled($user->authIdentifier()),
        ]);
    }
}
