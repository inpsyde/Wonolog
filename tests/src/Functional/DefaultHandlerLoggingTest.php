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

namespace Inpsyde\Wonolog\Tests\Functional;

use Inpsyde\Wonolog;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\HookListener;
use Inpsyde\Wonolog\Tests\FunctionalTestCase;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @runTestsInSeparateProcesses
 */
class DefaultHandlerLoggingTest extends FunctionalTestCase
{
    public function testLogCustomHook()
    {
        $dir = vfsStream::setup('TestDir');
        $url = $dir->url();
        putenv("WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}");

        Wonolog\bootstrap();

        do_action(Wonolog\LOG, new Error('Log via hook happened!', Channels::DB));

        $path = date('Y/m/d') . '.log';

        self::assertTrue($dir->hasChild($path));

        /** @var vfsStreamFile $file */
        $file = $dir->getChild($path);

        $this->checkLoggedMessage(
            'Log via hook happened!',
            Channels::DB,
            Logger::ERROR,
            $file->getContent()
        );
    }

    public function testLogCoreHook()
    {
        $dir = vfsStream::setup('TestDir');
        $url = $dir->url();
        putenv("WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}");

        Wonolog\bootstrap();

        do_action('muplugins_loaded');

        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('WP mail is broken');
        $error->shouldReceive('get_error_data')->andReturn([]);
        $error->shouldReceive('get_error_codes')->andReturn(['wp_mail_failed']);

        do_action('wp_mail_failed', $error);

        $path = date('Y/m/d') . '.log';

        self::assertTrue($dir->hasChild($path));

        /** @var vfsStreamFile $file */
        $file = $dir->getChild($path);

        $this->checkLoggedMessage(
            'WP mail is broken',
            Channels::HTTP,
            Logger::ERROR,
            $file->getContent()
        );
    }

    public function testCoreHookNotLoggedIfNoHookListeners()
    {
        $dir = vfsStream::setup('TestDir');
        $url = $dir->url();
        putenv("WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}");

        Wonolog\bootstrap(null, Wonolog\USE_DEFAULT_NONE);

        do_action('muplugins_loaded');

        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('WP mail is broken');
        $error->shouldReceive('get_error_data')->andReturn([]);
        $error->shouldReceive('get_error_codes')->andReturn(['wp_mail_failed']);

        do_action('wp_mail_failed', $error);

        $path = date('Y/m/d') . '.log';

        self::assertFalse($dir->hasChild($path));
    }

    public function testCoreHookWithSingleListener()
    {
        $dir = vfsStream::setup('TestDir');
        $url = $dir->url();
        putenv("WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}");

        Wonolog\bootstrap(null, Wonolog\USE_DEFAULT_HANDLER)
            ->useHookListener(new HookListener\MailerListener());

        do_action('muplugins_loaded');

        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('WP mail is broken');
        $error->shouldReceive('get_error_data')->andReturn([]);
        $error->shouldReceive('get_error_codes')->andReturn(['wp_mail_failed']);

        do_action('wp_mail_failed', $error);

        $path = date('Y/m/d') . '.log';

        self::assertTrue($dir->hasChild($path));

        /** @var vfsStreamFile $file */
        $file = $dir->getChild($path);

        $this->checkLoggedMessage(
            'WP mail is broken',
            Channels::HTTP,
            Logger::ERROR,
            $file->getContent(),
            false
        );
    }

    /**
     * @param string $message
     * @param string $channel
     * @param int $level
     * @param string $text
     * @param bool $wpContext
     * @return void
     */
    private function checkLoggedMessage(
        string $message,
        string $channel,
        int $level,
        string $text,
        bool $wpContext = true
    ) {

        $regex = '^\[([^\]]+)\] ';
        $regex .= $channel . '\.';
        $regex .= Logger::getLevelName($level) . ': ';
        $regex .= $message . ' \[\] ';
        $regex .= $wpContext
            ? preg_quote('{"wp":{"doing_cron":false,"doing_ajax":false,"is_admin":false}}', '~')
            : '\[\]';

        $regex .= '$';

        $matched = preg_match("~{$regex}~", $text, $matches);

        $timestamp = strtotime($matches[1]);
        static::assertTrue((time() - $timestamp) < 30);

        self::assertSame(1, $matched);
    }
}
