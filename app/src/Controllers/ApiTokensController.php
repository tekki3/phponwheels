<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AuthPage;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\Mfa;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class ApiTokensController implements Handler
{
    public function __construct(
        private readonly Tokens $tokens, private readonly Authenticator $auth,
        private readonly Mfa $mfa, private readonly AuthPage $page,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $user = $this->auth->user();
        assert($user !== null);

        return $this->page->render('auth/tokens', 'API tokens', [
            'tokens' => $this->tokens->listed($user->authIdentifier()), 'issuedToken' => null,
            'enabled' => $this->mfa->enabled($user->authIdentifier()),
        ]);
    }
}
