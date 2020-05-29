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
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @runTestsInSeparateProcesses
 */
class LogLevelTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL');
        parent::tearDown();
    }

    public function testDefaultLevelByEnvString()
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL');

        $logLevel = new LogLevel();

        self::assertSame(Logger::CRITICAL, $logLevel->defaultMinLevel());
    }

    public function testDefaultLevelByEnvNum()
    {
        putenv('WONOLOG_DEFAULT_MIN_LEVEL=500');

        $logLevel = new LogLevel();

        self::assertSame(Logger::CRITICAL, $logLevel->defaultMinLevel());
    }

    public function testDefaultLevelByConstantNone()
    {

        $logLevel = new LogLevel();

        self::assertSame(Logger::ERROR, $logLevel->defaultMinLevel());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultLevelByConstantLog()
    {

        define('WP_DEBUG_LOG', true);

        $logLevel = new LogLevel();

        self::assertSame(Logger::DEBUG, $logLevel->defaultMinLevel());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultLevelByConstantDebug()
    {
        define('WP_DEBUG', true);

        $logLevel = new LogLevel();

        self::assertFalse(defined('WP_DEBUG_LOG'));
        self::assertSame(Logger::DEBUG, $logLevel->defaultMinLevel());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultLevelByConstantLogFalse()
    {

        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        $logLevel = new LogLevel();

        self::assertSame(Logger::ERROR, $logLevel->defaultMinLevel());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultLevelByEnvOverConstants()
    {

        putenv('WONOLOG_DEFAULT_MIN_LEVEL=EMERGENCY');
        define('WP_DEBUG_LOG', false);
        define('WP_DEBUG', true);

        $logLevel = new LogLevel();

        self::assertSame(Logger::EMERGENCY, $logLevel->defaultMinLevel());
    }

    public function testCheckLevelAcceptsPositiveNumbers()
    {

        $logLevel = new LogLevel();

        self::assertSame(0, $logLevel->checkLevel(0));
        self::assertSame(27, $logLevel->checkLevel(27));
        self::assertSame(42, $logLevel->checkLevel(42));
        self::assertSame(0, $logLevel->checkLevel(-10));
    }

    public function estCheckLevelAcceptsDefinedLevelStrings()
    {

        $logLevel = new LogLevel();

        self::assertSame(Logger::CRITICAL, $logLevel->checkLevel('CRITICAL'));
        self::assertSame(Logger::ERROR, $logLevel->checkLevel('error'));
        self::assertSame(Logger::DEBUG, $logLevel->checkLevel('Debug'));
        self::assertSame(Logger::ALERT, $logLevel->checkLevel('aLeRt'));
        self::assertSame(Logger::EMERGENCY, $logLevel->checkLevel('emeRGEncy'));
        self::assertSame(Logger::INFO, $logLevel->checkLevel(' INFO '));
        self::assertSame(Logger::NOTICE, $logLevel->checkLevel(' nOtiCE'));
        self::assertSame(Logger::WARNING, $logLevel->checkLevel('Warning '));
        self::assertSame(0, $logLevel->checkLevel('MEH'));
    }
}
