<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PhpOnWheels\Api\ApiError;
use PhpOnWheels\Api\JsonBody;
use PhpOnWheels\Api\RateLimiter;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Auth\Mfa;
use PhpOnWheels\Auth\SecurityState;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Routing\Handler;

final class LoginController implements Handler
{
    public function __construct(
        private readonly Authenticator $auth,
        private readonly Mfa $mfa,
        private readonly Tokens $tokens,
        private readonly RateLimiter $limiter,
        private readonly SecurityState $state,
    ) {
    }

    public function handle(ServerRequest $request): Response
    {
        $body = JsonBody::fromRequest($request);
        $email = mb_strtolower(trim($body->string('email', 191)));
        $password = $body->string('password', 72);
        $code = $body->string('code', 20, required: false);
        $limit = $this->limiter->consume('api-login:' . $email, 5, 900);
        if (!$limit->allowed) {
            return (new ApiError(Status::TooManyRequests, 'Too many authentication attempts.'))->response()
                ->withHeader('Retry-After', (string) max(1, $limit->resetsAt - time()));
        }
        $user = $this->auth->validateCredentials($email, $password);
        if ($user === null) {
            throw new ApiError(Status::Unauthorized, 'Invalid credentials or second factor.');
        }
        $version = $this->state->version($user->authIdentifier());
        if ($this->mfa->enabled($user->authIdentifier()) && !$this->mfa->verify($user->authIdentifier(), $code)) {
            throw new ApiError(Status::Unauthorized, 'Invalid credentials or second factor.');
        }
        $token = $this->tokens->jwt($user, ['profile:read'], securityVersion: $version);

        return Response::json(['access_token' => $token->value, 'token_type' => 'Bearer', 'expires_in' => 900]);
    }
}
