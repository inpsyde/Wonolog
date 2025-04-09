<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\MonologV3;

class Levels
{
    /**
     * Detailed debug information
     */
    public const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = 200;

    /**
     * Uncommon events
     */
    public const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 300;

    /**
     * Runtime errors
     */
    public const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 550;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = 600;

    /**
     * This is a static variable and not a constant to serve as an extension point for custom levels
     *
     * @var array<int, string> $levels Logging levels with the levels as key
     *
     * @phpstan-var array<int, string> $levels Logging levels with the levels as key
     */
    protected static $levels = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    /**
     * @return array<string, int>
     */
    public static function allLevels(): array
    {
        return array_flip(static::$levels);
    }
}
