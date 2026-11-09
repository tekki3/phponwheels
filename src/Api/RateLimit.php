<?php

declare(strict_types=1);

namespace PhpOnWheels\Api;

use Closure;
use PhpOnWheels\Container\RequestScope;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Middleware\Middleware;

final class RateLimit implements Middleware
{
    public function __construct(
        private readonly string $bucket = 'api',
        private readonly int $limit = 60,
        private readonly int $windowSeconds = 60,
    ) {
        if ($bucket === '' || $limit < 1 || $windowSeconds < 1 || $windowSeconds > 86400) {
            throw new \InvalidArgumentException('Invalid API rate-limit policy.');
        }
    }

    public function process(ServerRequest $request, RequestScope $scope, Closure $next): Response
    {
        $identity = $scope->provided(Principal::class)
            ? $scope->get(Principal::class)->user->authIdentifier()
            : 'anonymous';
        $result = $scope->get(RateLimiter::class)->consume($this->bucket . ':' . $identity, $this->limit, $this->windowSeconds);
        $response = $result->allowed ? $next($request)
            : (new ApiError(Status::TooManyRequests, 'API rate limit exceeded.'))->response()
                ->withHeader('Retry-After', (string) max(1, $result->resetsAt - time()));

        return $response->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining)
            ->withHeader('X-RateLimit-Reset', (string) $result->resetsAt);
    }
}
