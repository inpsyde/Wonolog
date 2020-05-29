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
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogLevel
{

    /**
     * @var int
     */
    private static $minLevel;

    /**
     * @return LogLevel
     */
    public static function instance(): LogLevel
    {

        return new static();
    }

    /**
     * Returns the minimum default log level based on environment variable or WordPress debug settings
     * (in this order of priority).
     *
     * The level is set once per request and it is filterable.
     *
     * @return int
     */
    public function defaultMinLevel(): int
    {

        if (self::$minLevel !== null) {
            return self::$minLevel;
        }

        $envLevel = getenv('WONOLOG_DEFAULT_MIN_LEVEL');
        // here $minLevel is a string (raw env vars are always strings) or false
        $minLevel = $envLevel;

        $levels = Logger::getLevels();
        $minLevel = $this->checkLevel($minLevel, $levels);
        // Now here $min_level is surely a integer, but could be 0,
        // and in that case we set it from WP constants
        if (!$minLevel) {
            $const = defined('WP_DEBUG_LOG') ? 'WP_DEBUG_LOG' : 'WP_DEBUG';
            $minLevel = (defined($const) && constant($const)) ? Logger::DEBUG : Logger::ERROR;
        }

        self::$minLevel = $minLevel;

        return $minLevel;
    }

    /**
     * In Monolog/Wonolog are 2 ways to indicate a logger level: an numeric value and level "names".
     * Names are defined in the PSR-3 spec, int are used in Monolog for severity comparison.
     * This method always return a numerical representation of a log level.
     *
     * When a name is provided, the numeric value is obtained looking into a provided array of levels.
     * If that array is not provided `Monolog\Logger::getLevels()` is used.
     *
     * If there's no way to resolve the given level, `0` is returned. Any code that use this method
     * should check that returned value is a positive number before us it.
     *
     * @param int|string $level
     * @param array $levels
     * @return int
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function checkLevel($level, array $levels = []): int
    {
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration

        if (!$level) {
            return 0;
        }

        if (is_numeric($level)) {
            return (int)$level > 0 ? (int)$level : 0;
        }

        if (!is_string($level)) {
            return 0;
        }

        $level = strtoupper(trim($level));

        $levels or $levels = Logger::getLevels();

        if (array_key_exists($level, $levels)) {
            return $levels[$level];
        }

        return 0;
    }
}
