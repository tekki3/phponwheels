<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\ApiDefinition;
use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class OpenApiController implements Handler
{
    public function handle(ServerRequest $request): Response
    {
        return Response::json(ApiDefinition::document()->document());
    }
}
