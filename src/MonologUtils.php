<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Logger;

class MonologUtils
{
    public static function version(): int
    {
        return Logger::API;
    }
}
