<?php

declare(strict_types=1);

namespace PhpOnWheels\Tests\Support;

/**
 * A constructor the container cannot satisfy: no class type, no default.
 */
final class NeedsScalar
{
    public function __construct(
        public readonly string $dsn,
    ) {
    }
}
