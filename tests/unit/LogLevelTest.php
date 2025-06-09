<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;

/**
 * @runTestsInSeparateProcesses
 */
class LogLevelTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvString(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL');

        static::assertSame(Levels::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvNum(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=500');

        static::assertSame(Levels::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantNone(): void
    {
        static::assertSame(Levels::WARNING, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLog(): void
    {
        define('WP_DEBUG_LOG', true);

        static::assertSame(Levels::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantDebug(): void
    {
        define('WP_DEBUG', true);

        static::assertFalse(defined('WP_DEBUG_LOG'));
        static::assertSame(Levels::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLogFalse(): void
    {
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        static::assertSame(Levels::WARNING, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvOverConstants(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=EMERGENCY');
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        static::assertSame(Levels::EMERGENCY, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testNormalizeLevelNormalizeToClosestValidLevel(): void
    {
        static::assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(0));
        static::assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(-10));
        static::assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(LogLevel::DEBUG - 10));
        static::assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(LogLevel::INFO - 1));
        static::assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::INFO));
        static::assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::INFO + 1));
        static::assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::NOTICE - 1));
        static::assertSame(LogLevel::NOTICE, LogLevel::normalizeLevel(LogLevel::NOTICE));
    }

    /**
     * @test
     */
    public function testCheckLevelAcceptsDefinedLevelStrings(): void
    {
        static::assertSame(Levels::CRITICAL, LogLevel::normalizeLevel('CRITICAL'));
        static::assertSame(Levels::ERROR, LogLevel::normalizeLevel('error'));
        static::assertSame(Levels::DEBUG, LogLevel::normalizeLevel('Debug'));
        static::assertSame(Levels::ALERT, LogLevel::normalizeLevel('aLeRt'));
        static::assertSame(Levels::EMERGENCY, LogLevel::normalizeLevel('emeRGEncy'));
        static::assertSame(Levels::INFO, LogLevel::normalizeLevel(' INFO '));
        static::assertSame(Levels::NOTICE, LogLevel::normalizeLevel(' nOtiCE'));
        static::assertSame(Levels::WARNING, LogLevel::normalizeLevel('Warning '));
        static::assertNull(LogLevel::normalizeLevel('MEH'));
    }
}
