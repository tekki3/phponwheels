<?php

declare(strict_types=1);

namespace PhpOnWheels\Routing;

enum Outcome
{
    case Found;
    case NotFound;
    case MethodNotAllowed;
}
