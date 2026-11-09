<?php

declare(strict_types=1);

namespace PhpOnWheels\Tests\Support;

final class NeedsCounter
{
    public function __construct(
        public readonly Counter $counter,
    ) {
    }
}
