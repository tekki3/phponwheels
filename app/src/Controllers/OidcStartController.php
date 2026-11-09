<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AuthPage;
use PhpOnWheels\Auth\AuthenticationFailed;
use PhpOnWheels\Auth\Oidc\OidcClient;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class OidcStartController implements Handler
{
    public function __construct(private readonly OidcClient $client, private readonly AuthPage $page)
    {
    }

    public function handle(ServerRequest $request): Response
    {
        try {
            return Response::redirect($this->client->start($request->attribute('provider') ?? ''))
                ->withHeader('Cache-Control', 'no-store');
        } catch (AuthenticationFailed $exception) {
            return $this->page->back('/login', $exception->getMessage(), true);
        }
    }
}
