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

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Inpsyde\Wonolog\Data\FailedLogin;
use Brain\Monkey\Functions;
use Monolog\Logger;

class FailedLoginTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testData(): void
    {
        $transient = false;

        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
        $callback = static function (string $name, $value = null) use (&$transient) {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
            // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration
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
                static::assertArrayHasKey('ip', $context);
                static::assertArrayHasKey('ip_from', $context);
                static::assertArrayHasKey('username', $context);
                static::assertSame('h4ck3rb0y', $context['username']);
                static::assertSame(Channels::SECURITY, $failedLogin->channel());
            }
            $failedLogin = new FailedLogin('h4ck3rb0y');
        }

        $expectedLoggedLevels = [
            3 => Levels::NOTICE,
            23 => Levels::NOTICE,
            43 => Levels::NOTICE,
            63 => Levels::NOTICE,
            83 => Levels::NOTICE,
            183 => Levels::WARNING,
            283 => Levels::WARNING,
            383 => Levels::WARNING,
            483 => Levels::WARNING,
            583 => Levels::WARNING,
            683 => Levels::ERROR,
            783 => Levels::ERROR,
            883 => Levels::ERROR,
            983 => Levels::ERROR,
            1183 => Levels::CRITICAL,
            1383 => Levels::CRITICAL,
            1583 => Levels::CRITICAL,
        ];

        static::assertSame($expectedLoggedLevels, $logged);

        $format = "%d failed login attempts from username 'h4ck3rb0y' in last 5 minutes";

        foreach (array_keys($expectedLoggedLevels) as $i => $levelNum) {
            static::assertSame($messages[$i], sprintf($format, $levelNum));
        }
    }

    /**
     * @test
     */
    public function testMessage(): void
    {
        $transient = false;

        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
        $callback = static function (string $name, $value = null) use (&$transient) {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
            // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration
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

        $this->assertSame(
            sprintf($expectedMessageFormat, 1, 'h4ck3rb0y'),
            $firstFailedLogin->message()
        );

        $this->assertSame(
            sprintf($expectedMessageFormat, 1, 'h4ck3rb0y'),
            $firstFailedLogin->message()
        );

        $this->assertSame(
            sprintf($expectedMessageFormat, 2, 'h4ck3rb0y'),
            $secondFailedLogin->message()
        );

        $this->assertSame(
            sprintf($expectedMessageFormat, 2, 'h4ck3rb0y'),
            $secondFailedLogin->message()
        );
    }
}
