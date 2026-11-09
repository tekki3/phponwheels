<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PhpOnWheels\Api\Principal;
use PhpOnWheels\Api\Tokens;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class RevokeController implements Handler
{
    public function __construct(private readonly Principal $principal, private readonly Tokens $tokens)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        $this->tokens->revoke($this->principal->user->authIdentifier(), $this->principal->tokenId);

        return Response::noContent();
    }
}
