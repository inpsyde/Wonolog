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

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\LogsFolder;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @runTestsInSeparateProcesses
 */
class LogsFolderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsInsideContent()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder();

        static::assertSame($dir->url() . '/www/wp-content/uploads/wonolog/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/uploads/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsOutsideContent()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders(false);

        $folder = LogsFolder::determineFolder();

        static::assertSame($dir->url() . '/www/uploads/wonolog/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/uploads/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsOutsideContentButErrored()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders(false, false);

        $folder = LogsFolder::determineFolder();

        static::assertSame($dir->url() . '/www/wp-content/wonolog/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenWpLogConstantDefinedAsPath()
    {
        $dir = $this->setupFolders();
        define('WP_DEBUG_LOG', $dir->url() . '/tmp/wp.log');

        $folder = LogsFolder::determineFolder();

        static::assertSame($dir->url() . '/tmp/wonolog/', $folder);
        static::assertFalse(file_exists($dir->url() . '/tmp/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenErrorLogFileConstantDefinedAsPath()
    {
        $dir = $this->setupFolders();
        define('WP_DEBUG_LOG', true);
        define('ERRORLOGFILE', $dir->url() . '/tmp/errorlog.log');

        $folder = LogsFolder::determineFolder();

        static::assertSame($dir->url() . '/tmp/wonolog/', $folder);
        static::assertFalse(file_exists($dir->url() . '/tmp/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineCustomWhenUploadsInsideContentWithAppendedWonologDir()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/uploads');

        static::assertSame($dir->url() . '/www/wp-content/uploads/wonolog/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/uploads/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineCustomWhenUploadsInsideContentWithoutAppendedWonologDir()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/uploads/logs');

        static::assertSame($dir->url() . '/www/wp-content/uploads/logs/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/uploads/logs/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineCustomInWpContentWithAppendedWonologDir()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR);

        static::assertSame($dir->url() . '/www/wp-content/wonolog/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/wonolog/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineCustomInWpContentWithoutAppendedWonologDir()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/logs');

        static::assertSame($dir->url() . '/www/wp-content/logs/', $folder);
        static::assertTrue(file_exists($dir->url() . '/www/wp-content/logs/.htaccess'));
    }

    /**
     * @test
     */
    public function testDetermineCustomOutsidePublic()
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder($dir->url() . '/tmp/logs');

        static::assertSame($dir->url() . '/tmp/logs/', $folder);
        static::assertFalse(file_exists($dir->url() . '/tmp/logs/.htaccess'));
    }

    /**
     * @param bool $uploadsNested
     * @param bool $uploadsOk
     * @return vfsStreamDirectory
     */
    private function setupFolders(bool $uploadsNested = true, $uploadsOk = true): vfsStreamDirectory
    {
        $dir = vfsStream::setup('root', 0777);
        $structure = [
            'tmp' => [],
            'www' => [
                'wp' => ['wp-includes' => [], 'wp-admin' => []],
                'wp-content' => []
            ],
        ];

        $uploadsNested
            ? $structure['www']['wp-content']['uploads'] = []
            : $structure['www']['uploads'] = [];

        vfsStream::create($structure);

        define('WP_CONTENT_DIR', $dir->url() . '/www/wp-content');

        Monkey\Functions\when('wp_upload_dir')
            ->alias(static function () use ($uploadsNested, $uploadsOk, $dir): array {
                $path = $uploadsNested ? '/www/wp-content/uploads' : '/www/uploads';
                return $uploadsOk ? ['basedir' => $dir->url() . $path] : ['error' => 'error'];
            });

        return $dir;
    }
}
