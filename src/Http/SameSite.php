<?php

declare(strict_types=1);

namespace PhpOnWheels\Http;

enum SameSite: string
{
    case Strict = 'Strict';
    case Lax = 'Lax';
    case None = 'None';
}
