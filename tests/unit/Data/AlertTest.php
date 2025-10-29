<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class AlertTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testImplementsLogDataInterface(): void
    {
        $alert = new Alert('Test alert message', Channels::DEBUG);

        static::assertInstanceOf(LogData::class, $alert);
    }

    /**
     * @test
     */
    public function testLevelAlwaysReturnsAlertConstant(): void
    {
        $alert = new Alert('Test message', Channels::DEBUG);

        static::assertSame(LogLevel::ALERT, $alert->level());
        static::assertSame(550, $alert->level());
    }

    /**
     * @test
     */
    public function testLevelIsIndependentOfChannel(): void
    {
        $debugAlert = new Alert('Alert message', Channels::DEBUG);
        $securityAlert = new Alert('Alert message', Channels::SECURITY);
        $dbAlert = new Alert('Alert message', Channels::DB);
        $networkAlert = new Alert('Alert message', Channels::NETWORK);

        static::assertSame(LogLevel::ALERT, $debugAlert->level());
        static::assertSame(LogLevel::ALERT, $securityAlert->level());
        static::assertSame(LogLevel::ALERT, $dbAlert->level());
        static::assertSame(LogLevel::ALERT, $networkAlert->level());
    }

    /**
     * @test
     */
    public function testLevelIsIndependentOfContext(): void
    {
        $alertWithoutContext = new Alert('Alert message', Channels::DEBUG);
        $alertWithSimpleContext = new Alert('Alert message', Channels::DEBUG, ['key' => 'value']);
        $alertWithComplexContext = new Alert('Alert message', Channels::DEBUG, [
            'user_id' => 123,
            'severity' => 'high',
            'metadata' => ['environment' => 'production'],
        ]);

        static::assertSame(LogLevel::ALERT, $alertWithoutContext->level());
        static::assertSame(LogLevel::ALERT, $alertWithSimpleContext->level());
        static::assertSame(LogLevel::ALERT, $alertWithComplexContext->level());
    }

    /**
     * @test
     */
    public function testLevelIsIndependentOfMessage(): void
    {
        $shortAlert = new Alert('Alert', Channels::DEBUG);
        $longAlert = new Alert(
            'This is a very long alert message with lots of details',
            Channels::DEBUG
        );
        $emptyAlert = new Alert('', Channels::DEBUG);

        static::assertSame(LogLevel::ALERT, $shortAlert->level());
        static::assertSame(LogLevel::ALERT, $longAlert->level());
        static::assertSame(LogLevel::ALERT, $emptyAlert->level());
    }
}
