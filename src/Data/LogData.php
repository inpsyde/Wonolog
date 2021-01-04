<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

interface LogData
{
    public const MESSAGE = 'message';
    public const LEVEL = 'level';
    public const CHANNEL = 'channel';
    public const CONTEXT = 'context';

    /**
     * @return int
     */
    public function level(): int;

    /**
     * @return string
     */
    public function message(): string;

    /**
     * @return string
     */
    public function channel(): string;

    /**
     * @return array
     */
    public function context(): array;
}
