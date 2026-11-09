<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class SwaggerController implements Handler
{
    public function handle(ServerRequest $request): Response
    {
        return (new \PhpOnWheels\Api\SwaggerController())->handle($request);
    }
}
