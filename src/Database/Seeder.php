<?php

declare(strict_types=1);

namespace PhpOnWheels\Database;

interface Seeder
{
    public function run(Connection $database): void;
}
