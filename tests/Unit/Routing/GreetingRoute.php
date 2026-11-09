<?php

declare(strict_types=1);

namespace PhpOnWheels\Tests\Unit\Routing;

use PhpOnWheels\Http\Response;
use PhpOnWheels\Http\ServerRequest;
use PhpOnWheels\Routing\Handler;

final class GreetingRoute implements Handler
{
    public function handle(ServerRequest $request): Response
    {
        return Response::text('ok');
    }
}
