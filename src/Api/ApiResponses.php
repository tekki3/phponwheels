<?php

declare(strict_types=1);

namespace PhpOnWheels\Api;

use Closure;
use PhpOnWheels\Container\RequestScope;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Http\Status;
use PhpOnWheels\Log\Level;
use PhpOnWheels\Log\Logger;
use PhpOnWheels\Middleware\Middleware;
use Throwable;

final class ApiResponses implements Middleware
{
    public function __construct(private readonly string $prefix = '/api')
    {
        if ($prefix === '' || $prefix[0] !== '/' || str_ends_with($prefix, '/')) {
            throw new \InvalidArgumentException('Use an absolute API prefix without a trailing slash.');
        }
    }

    public function process(ServerRequest $request, RequestScope $scope, Closure $next): Response
    {
        if ($request->uri->path !== $this->prefix && !str_starts_with($request->uri->path, $this->prefix . '/')) {
            return $next($request);
        }
        try {
            $response = $next($request);
        } catch (ApiError $error) {
            $response = $error->response();
        } catch (Throwable $error) {
            $scope->get(Logger::class)->log(Level::Error, 'API request failed', ['exception' => $error::class]);
            $response = (new ApiError(Status::InternalServerError, 'An unexpected API error occurred.'))->response();
        }
        if ($response->status->value >= 400 && !str_contains($response->headers->first('Content-Type') ?? '', 'json')) {
            $converted = (new ApiError($response->status, $response->status->reasonPhrase()))->response();
            foreach (['Allow', 'WWW-Authenticate', 'Retry-After', 'X-Request-ID'] as $header) {
                $value = $response->headers->first($header);
                if ($value !== null) {
                    $converted = $converted->withHeader($header, $value);
                }
            }
            $response = $converted;
        }

        return $response->withHeader('Cache-Control', 'no-store');
    }
}
