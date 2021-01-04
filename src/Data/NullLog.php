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

final class NullLog implements LogData
{
    private const LOG_LEVEL = -1;

    /**
     * @return int
     */
    public function level(): int
    {
        return self::LOG_LEVEL;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function channel(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function context(): array
    {
        return [];
    }
}
