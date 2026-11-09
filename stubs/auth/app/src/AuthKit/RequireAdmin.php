<?php

declare(strict_types=1);

namespace App\AuthKit;

use Closure;
use PhpOnWheels\Auth\Authenticator;
use PhpOnWheels\Container\RequestScope;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Middleware\Middleware;

final class RequireAdmin implements Middleware
{
    public function process(ServerRequest $request, RequestScope $scope, Closure $next): Response
    {
        $user = $scope->get(Authenticator::class)->user();
        if (!$user instanceof User || !$user->isAdmin) {
            return Response::text('Administrator access is required.', Status::Forbidden)->withHeader('Cache-Control', 'no-store');
        }

        return $next($request);
    }
}
