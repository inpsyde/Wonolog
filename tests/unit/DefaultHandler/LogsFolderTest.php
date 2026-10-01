<?php

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
final class LogsFolderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsInsideContent(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder();

        $this->assertSame($dir->url() . '/www/wp-content/uploads/wonolog/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/uploads/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsOutsideContent(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders(uploadsIntoWpContent: false);

        $folder = LogsFolder::determineFolder();

        $this->assertSame($dir->url() . '/www/uploads/wonolog/', $folder);
        $this->assertFileExists($dir->url() . '/www/uploads/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenUploadsOutsideContentButErrored(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders(uploadsIntoWpContent: false, uploadsDirError: true);

        $folder = LogsFolder::determineFolder();

        $this->assertSame($dir->url() . '/www/wp-content/wonolog/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenWpLogConstantDefinedAsPath(): void
    {
        $dir = $this->setupFolders();
        define('WP_DEBUG_LOG', $dir->url() . '/tmp/wp.log');

        $folder = LogsFolder::determineFolder();

        $this->assertSame($dir->url() . '/tmp/wonolog/', $folder);
        $this->assertFileNotExists($dir->url() . '/tmp/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineDefaultWhenErrorLogFileConstantDefinedAsPath(): void
    {
        $dir = $this->setupFolders();
        define('WP_DEBUG_LOG', true);
        define('ERRORLOGFILE', $dir->url() . '/tmp/errorlog.log');

        $folder = LogsFolder::determineFolder();

        $this->assertSame($dir->url() . '/tmp/wonolog/', $folder);
        $this->assertFileNotExists($dir->url() . '/tmp/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineCustomWhenUploadsInsideContentWithAppendedWonologDir(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/uploads');

        $this->assertSame($dir->url() . '/www/wp-content/uploads/wonolog/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/uploads/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineCustomWhenUploadsInsideContentWithoutAppendedWonologDir(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/uploads/logs');

        $this->assertSame($dir->url() . '/www/wp-content/uploads/logs/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/uploads/logs/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineCustomInWpContentWithAppendedWonologDir(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR);

        $this->assertSame($dir->url() . '/www/wp-content/wonolog/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/wonolog/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineCustomInWpContentWithoutAppendedWonologDir(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder(WP_CONTENT_DIR . '/logs');

        $this->assertSame($dir->url() . '/www/wp-content/logs/', $folder);
        $this->assertFileExists($dir->url() . '/www/wp-content/logs/.htaccess');
    }

    /**
     * @test
     */
    public function testDetermineCustomOutsidePublic(): void
    {
        define('WP_DEBUG_LOG', true);
        $dir = $this->setupFolders();

        $folder = LogsFolder::determineFolder($dir->url() . '/tmp/logs');

        $this->assertSame($dir->url() . '/tmp/logs/', $folder);
        $this->assertFileNotExists($dir->url() . '/tmp/logs/.htaccess');
    }

    /**
     * @param bool $uploadsIntoWpContent
     * @param bool $uploadsDirError
     * @return vfsStreamDirectory
     */
    private function setupFolders(
        bool $uploadsIntoWpContent = true,
        bool $uploadsDirError = false
    ): vfsStreamDirectory {

        $dir = vfsStream::setup('root', 0777);
        $structure = [
            'tmp' => [],
            'www' => [
                'wp' => ['wp-includes' => [], 'wp-admin' => []],
                'wp-content' => [],
            ],
        ];

        $uploadsIntoWpContent
            ? $structure['www']['wp-content']['uploads'] = []
            : $structure['www']['uploads'] = [];

        vfsStream::create($structure);

        define('WP_CONTENT_DIR', $dir->url() . '/www/wp-content');

        Monkey\Functions\when('wp_upload_dir')
            ->alias(
                static function () use ($uploadsIntoWpContent, $uploadsDirError, $dir): array {
                    $path = $uploadsIntoWpContent ? '/www/wp-content/uploads' : '/www/uploads';
                    return $uploadsDirError
                        ? ['error' => 'error']
                        : ['basedir' => $dir->url() . $path];
                }
            );

        return $dir;
    }
}
