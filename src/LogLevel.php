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

namespace Inpsyde\Wonolog;

use Monolog\Logger;

/**
 * Utility object used to build default min logging level based WordPress and environment settings.
 * It also has a method to check the validity of a value as level identifier.
 */
abstract class LogLevel
{
    /**
     * @var array<string, int>|null
     */
    private static $allLevels;

    /**
     * @var int|null
     */
    private static $minLevel;

    /**
     * Returns the minimum default log level based on environment variable or WordPress debug
     * settings (in this order of priority).
     *
     * The level is set once per request and it is filterable.
     *
     * @return int
     */
    final public static function defaultMinLevel(): int
    {
        if (self::$minLevel !== null) {
            return self::$minLevel;
        }

        $envLevel = getenv('WONOLOG_DEFAULT_MIN_LEVEL');

        $minLevel = static::normalizeLevel($envLevel ?: null);

        // If no valid level is defined via env var, then let's resort to WP constants.
        if (!$minLevel) {
            $const = defined('WP_DEBUG_LOG') ? 'WP_DEBUG_LOG' : 'WP_DEBUG';
            $minLevel = (defined($const) && constant($const)) ? Logger::DEBUG : Logger::WARNING;
        }

        self::$minLevel = $minLevel;

        return $minLevel;
    }

    /**
     * In Monolog/Wonolog there're two ways to indicate a logger level:
     * - a integer value
     * - level "names".
     * Names are defined in the PSR-3 specification, integers are used in Monolog to allow severity
     * comparison: the higher the number, the higher the severity.
     *
     * This method always return a numerical representation of a log level.
     *
     * When a name is provided, the numeric value is obtained doing a lookup in
     * `Monolog\Logger::getLevels()` that returns a map of level names to level integer values.
     *
     * If there's no way to resolve the given level, null is returned.
     *
     * @param mixed $level
     * @return int|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    final public static function normalizeLevel($level): ?int
    {
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration

        if (!$level) {
            return null;
        }

        if (self::$allLevels === null) {
            /** @var array<string, int> $loggerLevels */
            $loggerLevels = Logger::getLevels();
            self::$allLevels = $loggerLevels;
        }

        if (is_numeric($level)) {
            $level = (int)$level;

            return in_array($level, self::$allLevels, true) ? $level : null;
        }

        if (!is_string($level)) {
            return null;
        }

        return self::$allLevels[strtoupper(trim($level))] ?? null;
    }
}
