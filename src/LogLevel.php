<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Level;

/**
 * Utility object used to build default min logging level based WordPress and environment settings.
 * It also has a method to check the validity of a value as level identifier.
 */
abstract class LogLevel
{
    public const DEBUG = 100;
    public const INFO = 200;
    public const NOTICE = 250;
    public const WARNING = 300;
    public const ERROR = 400;
    public const CRITICAL = 500;
    public const ALERT = 550;
    public const EMERGENCY = 600;

    private const LEVELS = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    /** @var key-of<LogLevel::LEVELS>|null */
    private static ?int $minLevel = null;

    /**
     * @var array<array-key, key-of<LogLevel::LEVELS>|null>
     */
    private static array $mappedLevels = [];

    /**
     * @return array<value-of<LogLevel::LEVELS>, key-of<LogLevel::LEVELS>>
     */
    final public static function allLevels(): array
    {
        static $allLevels;
        if (!isset($allLevels)) {
            $allLevels = array_flip(self::LEVELS);
        }
        /** @var array<value-of<LogLevel::LEVELS>, key-of<LogLevel::LEVELS>> $allLevels */
        return $allLevels;
    }

    /**
     * Returns the minimum default log level based on Wonolog constant, environment variable or
     * WordPress debug settings (in this order of priority).
     *
     * The level is set once per request and it is filterable.
     *
     * @return key-of<LogLevel::LEVELS>
     */
    final public static function defaultMinLevel(): int
    {
        if (self::$minLevel !== null) {
            return self::$minLevel;
        }

        $configLevel = defined('WONOLOG_DEFAULT_MIN_LEVEL')
            ? \WONOLOG_DEFAULT_MIN_LEVEL
            : getenv('WONOLOG_DEFAULT_MIN_LEVEL');
        if (is_numeric($configLevel)) {
            $configLevel = (int) $configLevel;
        }
        if (!is_int($configLevel) && (!is_string($configLevel) || ($configLevel === ''))) {
            $configLevel = null;
        }

        $minLevel = static::normalizeLevel($configLevel);

        // If no valid level is defined via Wonolog config, then let's resort to WP constants.
        if ($minLevel === null) {
            $const = defined('WP_DEBUG_LOG') ? 'WP_DEBUG_LOG' : 'WP_DEBUG';
            $minLevel = (defined($const) && (constant($const) === false))
                ? self::WARNING
                : self::DEBUG;
        }

        self::$minLevel = $minLevel;

        return $minLevel;
    }

    /**
     * @param int $numLevel
     * @return string
     */
    final public static function toPsrLevel(int $numLevel): string
    {
        return match ($numLevel) {
            self::EMERGENCY => \Psr\Log\LogLevel::EMERGENCY,
            self::ALERT => \Psr\Log\LogLevel::ALERT,
            self::CRITICAL => \Psr\Log\LogLevel::CRITICAL,
            self::ERROR => \Psr\Log\LogLevel::ERROR,
            self::WARNING => \Psr\Log\LogLevel::WARNING,
            self::NOTICE => \Psr\Log\LogLevel::NOTICE,
            self::INFO => \Psr\Log\LogLevel::INFO,
            default => \Psr\Log\LogLevel::DEBUG,
        };
    }

    /**
     * @param string $psrLevel
     * @return int
     */
    final public static function toNumericLevel(string $psrLevel): int
    {
        return static::normalizeLevel($psrLevel) ?? self::DEBUG;
    }

    /**
     * In Monolog/Wonolog there are three ways to indicate a logger level:
     * - a integer value
     * - level "names".
     * - Monolog v3+ Level enum.
     * Names are defined in the PSR-3 specification, integers are used in Monolog to allow severity
     * comparison: the higher the number, the higher the severity.
     *
     * This method always return a numerical representation of a log level.
     *
     * If there's no way to resolve the given level, null is returned.
     *
     * @param mixed $level
     * @return key-of<LogLevel::LEVELS>|null
     */
    final public static function normalizeLevel(mixed $level): ?int
    {
        if ($level instanceof Level) {
            return $level->value;
        }

        $numeric = is_numeric($level);
        $string = !$numeric && is_string($level);

        if (!$numeric && !$string) {
            return null;
        }

        /** @var string|int $level */

        if (array_key_exists($level, self::$mappedLevels)) {
            return self::$mappedLevels[$level];
        }

        $allLevels = self::allLevels();

        if ($string) {
            /** @var string $level */
            $levelName = strtoupper(trim($level));
            self::$mappedLevels[$level] = $allLevels[$levelName] ?? null;

            return self::$mappedLevels[$level];
        }

        $level = (int) $level;
        if (in_array($level, $allLevels, true)) {
            self::$mappedLevels[$level] = $level;

            return $level;
        }

        $maxLevel = null;
        foreach ($allLevels as $validLevel) {
            if (($level > $validLevel) && (($maxLevel === null) || ($validLevel > $maxLevel))) {
                $maxLevel = $validLevel;
            }
        }

        self::$mappedLevels[$level] = $maxLevel ?? self::DEBUG;

        return self::$mappedLevels[$level];
    }

    /**
     * Private constructor to make the class non-instantiable
     */
    private function __construct()
    {
    }
}
