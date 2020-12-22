<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\LogLevel;

final class Alert implements LogData
{
    use LogDataTrait;

    public function level(): int
    {
        return LogLevel::ALERT;
    }
}
