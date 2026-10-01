<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

final class FailedLoginTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testData(): void
    {
        $transient = false;
        $callback = static function (string $name, mixed $value = null) use (&$transient): mixed {
            if ($value === null) {
                return $transient;
            }
            $transient = $value;

            return true;
        };

        Functions\when('get_site_transient')->alias($callback);
        Functions\when('set_site_transient')->alias($callback);

        $failedLogin = new FailedLogin('h4ck3rb0y');

        $logged = $messages = [];

        // Let's brute force!
        for ($i = 1; $i < 1600; $i++) {
            $level = $failedLogin->level();
            if ($level) {
                $logged[$i] = $level;
                $messages[] = $failedLogin->message();
                $context = $failedLogin->context();
                $this->assertArrayHasKey('ip', $context);
                $this->assertArrayHasKey('ip_from', $context);
                $this->assertArrayHasKey('username', $context);
                $this->assertSame('h4ck3rb0y', $context['username']);
                $this->assertSame(Channels::SECURITY, $failedLogin->channel());
            }
            $failedLogin = new FailedLogin('h4ck3rb0y');
        }

        $expectedLoggedLevels = [
            3 => LogLevel::NOTICE,
            23 => LogLevel::NOTICE,
            43 => LogLevel::NOTICE,
            63 => LogLevel::NOTICE,
            83 => LogLevel::NOTICE,
            183 => LogLevel::WARNING,
            283 => LogLevel::WARNING,
            383 => LogLevel::WARNING,
            483 => LogLevel::WARNING,
            583 => LogLevel::WARNING,
            683 => LogLevel::ERROR,
            783 => LogLevel::ERROR,
            883 => LogLevel::ERROR,
            983 => LogLevel::ERROR,
            1183 => LogLevel::CRITICAL,
            1383 => LogLevel::CRITICAL,
            1583 => LogLevel::CRITICAL,
        ];

        $this->assertSame($expectedLoggedLevels, $logged);

        $format = "%d failed login attempts from username 'h4ck3rb0y' in last 5 minutes";

        foreach (array_keys($expectedLoggedLevels) as $i => $levelNum) {
            $this->assertSame($messages[$i], sprintf($format, $levelNum));
        }
    }

    /**
     * @test
     */
    public function testMessage(): void
    {
        $transient = false;

        $callback = static function (string $name, mixed $value = null) use (&$transient): mixed {
            if ($value === null) {
                return $transient;
            }
            $transient = $value;

            return true;
        };

        Functions\when('get_site_transient')->alias($callback);
        Functions\when('set_site_transient')->alias($callback);

        $expectedMessageFormat = "%d failed login attempts from username '%s' in last 5 minutes";

        $firstFailedLogin = new FailedLogin('h4ck3rb0y');
        $secondFailedLogin = new FailedLogin('h4ck3rb0y');

        $this->assertSame(
            sprintf($expectedMessageFormat, 1, 'h4ck3rb0y'),
            $firstFailedLogin->message()
        );

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame(
            sprintf($expectedMessageFormat, 1, 'h4ck3rb0y'),
            $firstFailedLogin->message()
        );

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame(
            sprintf($expectedMessageFormat, 1, 'h4ck3rb0y'),
            $firstFailedLogin->message()
        );

        $this->assertSame(
            sprintf($expectedMessageFormat, 2, 'h4ck3rb0y'),
            $secondFailedLogin->message()
        );

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame(
            sprintf($expectedMessageFormat, 2, 'h4ck3rb0y'),
            $secondFailedLogin->message()
        );
    }
}
