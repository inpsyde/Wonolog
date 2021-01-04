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

namespace Inpsyde\Wonolog\Tests\Unit;

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

        self::assertSame(Logger::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvNum(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=500');

        self::assertSame(Logger::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantNone(): void
    {
        self::assertSame(Logger::WARNING, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLog(): void
    {
        define('WP_DEBUG_LOG', true);

        self::assertSame(Logger::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantDebug(): void
    {
        define('WP_DEBUG', true);

        self::assertFalse(defined('WP_DEBUG_LOG'));
        self::assertSame(Logger::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLogFalse(): void
    {
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        self::assertSame(Logger::WARNING, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvOverConstants(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=EMERGENCY');
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        self::assertSame(Logger::EMERGENCY, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testCheckLevelAcceptsPositiveNumbers(): void
    {
        self::assertNull(LogLevel::normalizeLevel(0));
        self::assertSame(null, LogLevel::normalizeLevel(27));
        self::assertSame(250, LogLevel::normalizeLevel(250));
        self::assertNull(LogLevel::normalizeLevel(-10));
    }

    /**
     * @test
     */
    public function testCheckLevelAcceptsDefinedLevelStrings(): void
    {
        self::assertSame(Logger::CRITICAL, LogLevel::normalizeLevel('CRITICAL'));
        self::assertSame(Logger::ERROR, LogLevel::normalizeLevel('error'));
        self::assertSame(Logger::DEBUG, LogLevel::normalizeLevel('Debug'));
        self::assertSame(Logger::ALERT, LogLevel::normalizeLevel('aLeRt'));
        self::assertSame(Logger::EMERGENCY, LogLevel::normalizeLevel('emeRGEncy'));
        self::assertSame(Logger::INFO, LogLevel::normalizeLevel(' INFO '));
        self::assertSame(Logger::NOTICE, LogLevel::normalizeLevel(' nOtiCE'));
        self::assertSame(Logger::WARNING, LogLevel::normalizeLevel('Warning '));
        self::assertNull(LogLevel::normalizeLevel('MEH'));
    }
}
