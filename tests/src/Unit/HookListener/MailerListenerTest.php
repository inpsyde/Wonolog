<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\HookListener\MailerListener;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class MailerListenerTest extends TestCase
{

    public function testOnMailerInit()
    {
        $listener = new MailerListener();

        Actions\expectDone('phpmailer_init')
            ->once()
            ->whenHappen(
                static function (\PHPMailer $mailer) use ($listener) {

                    $log = $listener->update([$mailer]);
                    static::assertInstanceOf(NullLog::class, $log);
                }
            );

        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->whenHappen(
                static function (Debug $debug) {

                    static::assertSame('Test email!', $debug->message());
                    static::assertSame(Channels::HTTP, $debug->channel());
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

    public function testOnMailFailed()
    {
        $listener = new MailerListener();

        Functions\when('is_wp_error')
            ->alias(
                // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function ($thing): bool {
                    // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                    return $thing instanceof \WP_Error;
                }
            );

        Actions\expectDone('wp_mail_failed')
            ->once()
            ->whenHappen(
                static function (\WP_Error $error) use ($listener) {
                    $log = $listener->update([$error]);

                    static::assertInstanceOf(LogDataInterface::class, $log);
                    static::assertSame(Logger::ERROR, $log->level());
                    static::assertSame(Channels::HTTP, $log->channel());
                }
            );

        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Something when wrong!');
        $error->shouldReceive('get_error_codes')->andReturn([0]);
        $error->shouldReceive('get_error_data')->andReturn([]);

        do_action('wp_mail_failed', $error);
    }

    /**
     * Check for a consistent return type
     *
     * @see MailerListener::update()
     * @see MailerListener::on_mail_failed()
     */
    public function testOnMailFailedReturnType()
    {
        $listener = new MailerListener();

        Functions\expect('current_filter')
            ->once()
            ->andReturn('wp_mail_failed');

        Functions\expect('is_wp_error')
            ->once()
            ->andReturn(false);

        $this->assertInstanceOf(LogDataInterface::class, $listener->update([]));
    }
}
