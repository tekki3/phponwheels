<?php

declare(strict_types=1);

namespace PhpOnWheels\Middleware;

use Closure;
use PhpOnWheels\Container\RequestScope;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;

/**
 * A layer wrapped around request handling.
 *
 * Implementations are constructed during boot and shared by every request the
 * process serves, so they must hold no mutable state of their own. Anything
 * request-specific comes from the {@see RequestScope} passed in.
 *
 * Middleware may return early without calling `$next` — that is how a guard
 * such as CSRF rejects a request before the handler ever runs.
 */
interface Middleware
{
    /**
     * @param Closure(ServerRequest): Response $next
     */
    public function process(ServerRequest $request, RequestScope $scope, Closure $next): Response;
}
