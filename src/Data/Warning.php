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

use Monolog\Logger;

final class Warning implements LogData
{
    use LogDataTrait;

    public function level(): int
    {
        return Logger::WARNING;
    }
}
