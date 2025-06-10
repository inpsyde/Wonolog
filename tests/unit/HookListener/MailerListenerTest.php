<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\MailerListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use PHPMailer\PHPMailer;

class MailerListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testOnMailerInit(): void
    {
        $listener = new MailerListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(Debug::class))
            ->andReturnUsing(
                static function (Debug $debug): void {
                    static::assertSame('Test email!', $debug->message());
                    static::assertSame(Channels::NETWORK, $debug->channel());
                }
            );

        Actions\expectDone('phpmailer_init')
            ->once()
            ->whenHappen(
                static function (PHPMailer\PHPMailer $mailer) use ($listener, $updater): void {
                    $listener->update('phpmailer_init', [$mailer], $updater);
                }
            );

        $mailer = new PHPMailer\PHPMailer();
        do_action('phpmailer_init', $mailer);

        static::assertSame(2, $mailer->SMTPDebug);
        static::assertIsCallable($mailer->Debugoutput);

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
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
                    static::assertInstanceOf(LogData::class, $log);
                    static::assertSame(LogLevel::ERROR, $log->level());
                    static::assertSame(Channels::NETWORK, $log->channel());
                }
            );

        Actions\expectDone('wp_mail_failed')
            ->once()
            ->whenHappen(
                static function (\WP_Error $error) use ($listener, $updater): void {
                    $listener->update('wp_mail_failed', [$error], $updater);
                }
            );

        $error = \Mockery::mock(\WP_Error::class);
        $error->allows('get_error_message')->andReturn('Something when wrong!');
        $error->allows('get_error_codes')->andReturn([0]);
        $error->allows('get_error_data')->andReturn([]);

        do_action('wp_mail_failed', $error);
    }
}
