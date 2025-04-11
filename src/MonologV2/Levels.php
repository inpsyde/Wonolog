<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\MonologV2;

use Monolog\Logger;

class Levels
{
    /**
     * @return array<string, int>
     */
    public static function allLevels(): array
    {
        return Logger::getLevels();
    }
}
