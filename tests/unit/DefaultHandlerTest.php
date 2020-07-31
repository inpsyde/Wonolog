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

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @runTestsInSeparateProcesses
 */
class DefaultHandlerTest extends UnitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        Monkey\Functions\when('wp_is_stream')->alias(static function (string $path): bool {
            return strpos($path, '://') !== false;
        });

        Monkey\Functions\when('wp_normalize_path')->alias(static function (string $path): string {
            $wrapper = '';
            if (wp_is_stream($path)) {
                [$wrapper, $path] = explode('://', $path, 2);
                $wrapper .= '://';
            }

            $path = preg_replace( '|(?<=.)/+|', '/', str_replace('\\', '/', $path));
            ($path[0] === ':') and $path = ucfirst($path);

            return $wrapper . $path;
        });

        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path): bool {
            $path = wp_normalize_path($path);

            return file_exists($path) ? is_dir($path) : mkdir($path, 0777, true);
        });
    }

    /**
     * @test
     */
    public function testPathDefinedViaDebugLogConstantInWpContent(): void
    {
        $wpContent = vfsStream::setup('root', 0777)->url() . '/wp-content';
        define('WP_CONTENT_DIR', $wpContent);
        define('WP_DEBUG_LOG', "{$wpContent}/logs/debug.log");
        Monkey\Functions\when('wp_upload_dir')->justReturn(['basedir' => "{$wpContent}/uploads"]);

        $actual = DefaultHandler::new()->withDateBasedFileFormat('Ymd')->logFilePath();

        $date = date('Ymd');
        static::assertSame("{$wpContent}/logs/wonolog/{$date}.log", $actual);

        $htaccess = "{$wpContent}/logs/wonolog/.htaccess";
        static::assertTrue(file_exists($htaccess));
        static::assertStringContainsString('Deny from all', file_get_contents($htaccess));
    }

    /**
     * @test
     */
    public function testPathDefinedViaWpUploadDir(): void
    {
        $wpContent = vfsStream::setup('root', 0777)->url() . '/wp-content';
        define('WP_CONTENT_DIR', $wpContent);
        Monkey\Functions\when('wp_upload_dir')->justReturn(['basedir' => "{$wpContent}/uploads"]);

        $actual = DefaultHandler::new()->withDateBasedFileFormat('Ymd')->logFilePath();

        $date = date('Ymd');
        static::assertSame("{$wpContent}/uploads/wonolog/{$date}.log", $actual);

        $htaccess = "{$wpContent}/uploads/wonolog/.htaccess";
        static::assertTrue(file_exists($htaccess));
        static::assertStringContainsString('Deny from all', file_get_contents($htaccess));
    }

    /**
     * @test
     */
    public function testPathDefinedInContentDirBecauseErroredUploads(): void
    {
        $wpContent = vfsStream::setup('root', 0777)->url() . '/wp-content';
        define('WP_CONTENT_DIR', $wpContent);
        Monkey\Functions\when('wp_upload_dir')->justReturn(['error' => 'meh']);

        $actual = DefaultHandler::new()->withDateBasedFileFormat('Ymd')->logFilePath();

        $date = date('Ymd');
        static::assertSame("{$wpContent}/logs/wonolog/{$date}.log", $actual);

        $htaccess = "{$wpContent}/logs/wonolog/.htaccess";
        static::assertTrue(file_exists($htaccess));
        static::assertStringContainsString('Deny from all', file_get_contents($htaccess));
    }

    /**
     * @test
     */
    public function testCustomPathOutsideContent(): void
    {
        $dir = vfsStream::setup('root', 0777);
        vfsStream::create(
            [
                'logs' => [],
                'public' => [
                    'wp' => ['wp-includes' => [], 'wp-admin' => []],
                    'wp-content' => [],
                    'uploads' => [],
                ],
            ],
            $dir
        );

        $public = $dir->url() . '/public';

        define('WP_CONTENT_DIR', "{$public}/wp-content");
        Monkey\Functions\when('wp_upload_dir')->justReturn(['basedir' => "{$public}/uploads"]);

        $actual = DefaultHandler::new()
            ->withFolder($dir->url() . '/logs')
            ->withFilename('wonolog.log')
            ->logFilePath();

        static::assertSame($dir->url() . '/logs/wonolog.log', $actual);
        static::assertTrue(is_dir($dir->url() . '/logs'));
        static::assertFalse(file_exists($dir->url() . '/logs/.htaccess'));
    }
}
