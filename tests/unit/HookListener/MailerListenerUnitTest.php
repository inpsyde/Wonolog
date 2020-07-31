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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\MailerListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;

class MailerListenerUnitTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testOnMailerInit(): void
    {
        $listener = new MailerListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->shouldReceive('update')
            ->once()
            ->with(\Mockery::type(Debug::class))
            ->andReturnUsing(
                static function (Debug $debug) {
                    static::assertSame('Test email!', $debug->message());
                    static::assertSame(Channels::HTTP, $debug->channel());
                }
            );

        Actions\expectDone('phpmailer_init')
            ->once()
            ->whenHappen(
                static function (\PHPMailer $mailer) use ($listener, $updater) {
                    $listener->update('phpmailer_init', [$mailer], $updater);
                }
            );

        $mailer = \Mockery::mock(\PHPMailer::class);
        $mailer->SMTPDebug = 0;
        $mailer->Debugoutput = null;

        do_action('phpmailer_init', $mailer);

        static::assertSame(2, $mailer->SMTPDebug);
        static::assertIsCallable($mailer->Debugoutput);

        /** @var callable $callback */
        $callback = $mailer->Debugoutput;
        $callback('Test email!');
    }

    /**
     * @test
     */
    public function testOnMailFailed(): void
    {
        $listener = new MailerListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->shouldReceive('update')
            ->once()
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log) {
                    static::assertInstanceOf(LogData::class, $log);
                    static::assertSame(Logger::ERROR, $log->level());
                    static::assertSame(Channels::HTTP, $log->channel());
                }
            );

        Actions\expectDone('wp_mail_failed')
            ->once()
            ->whenHappen(
                static function (\WP_Error $error) use ($listener, $updater) {
                    $listener->update('wp_mail_failed', [$error], $updater);
                }
            );

        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Something when wrong!');
        $error->shouldReceive('get_error_codes')->andReturn([0]);
        $error->shouldReceive('get_error_data')->andReturn([]);

        do_action('wp_mail_failed', $error);
    }
}
