<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class LogLevelTest extends UnitTestCase
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

        $this->assertSame(LogLevel::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvNum(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=500');

        $this->assertSame(LogLevel::CRITICAL, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantNone(): void
    {
        $this->assertSame(LogLevel::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLog(): void
    {
        define('WP_DEBUG_LOG', true);

        $this->assertSame(LogLevel::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantDebug(): void
    {
        define('WP_DEBUG', true);

        $this->assertFalse(defined('WP_DEBUG_LOG'));
        $this->assertSame(LogLevel::DEBUG, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByConstantLogFalse(): void
    {
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        $this->assertSame(LogLevel::WARNING, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testDefaultLevelByEnvOverConstants(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=EMERGENCY');
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        $this->assertSame(LogLevel::EMERGENCY, LogLevel::defaultMinLevel());
    }

    /**
     * @test
     */
    public function testNormalizeLevelNormalizeToClosestValidLevel(): void
    {
        $this->assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(0));
        $this->assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(-10));
        $this->assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(LogLevel::DEBUG - 10));
        $this->assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel(LogLevel::INFO - 1));
        $this->assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::INFO));
        $this->assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::INFO + 1));
        $this->assertSame(LogLevel::INFO, LogLevel::normalizeLevel(LogLevel::NOTICE - 1));
        $this->assertSame(LogLevel::NOTICE, LogLevel::normalizeLevel(LogLevel::NOTICE));
    }

    /**
     * @test
     */
    public function testCheckLevelAcceptsDefinedLevelStrings(): void
    {
        $this->assertSame(LogLevel::CRITICAL, LogLevel::normalizeLevel('CRITICAL'));
        $this->assertSame(LogLevel::ERROR, LogLevel::normalizeLevel('error'));
        $this->assertSame(LogLevel::DEBUG, LogLevel::normalizeLevel('Debug'));
        $this->assertSame(LogLevel::ALERT, LogLevel::normalizeLevel('aLeRt'));
        $this->assertSame(LogLevel::EMERGENCY, LogLevel::normalizeLevel('emeRGEncy'));
        $this->assertSame(LogLevel::INFO, LogLevel::normalizeLevel(' INFO '));
        $this->assertSame(LogLevel::NOTICE, LogLevel::normalizeLevel(' nOtiCE'));
        $this->assertSame(LogLevel::WARNING, LogLevel::normalizeLevel('Warning '));
        $this->assertNull(LogLevel::normalizeLevel('MEH'));
    }
}
