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

namespace Inpsyde\Wonolog\Tests;

use Symfony\Component\Process\Process;

abstract class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var bool
     */
    private static $initialized = false;

    /***
     * @return void
     */
    abstract protected function bootstrapWonolog(): void;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('ABSPATH') || self::$initialized) {
            return;
        }

        self::initializeWp();

        require_once ABSPATH . 'wp-includes/plugin.php';

        add_action(
            'muplugins_loaded',
            function () {
                $this->bootstrapWonolog();
            }
        );

        require_once ABSPATH . 'wp-config.php';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (!defined('ABSPATH') || !file_exists(ABSPATH . 'wp-config.php')) {
            throw new \Exception('Cannot reset DB: WP is not initialized.');
        }

        self::runWpCliCommand(['db', 'drop', '--yes']);
        @unlink(ABSPATH . 'wp-config.php');

        self::$initialized = false;

        parent::tearDown();
    }

    /**
     * @param array $command
     * @return void
     */
    protected static function runWpCliCommand(array $command): void
    {
        static $cliPath;
        $cliPath or $cliPath = getenv('VENDOR_DIR') . '/bin';

        array_unshift($command, "{$cliPath}/wp");
        $command[] = "--path=" . ABSPATH;
        $command[] = "--quiet";
        $command[] = "--skip-plugins";
        $command[] = "--skip-themes";
        $command[] = "--allow-root";

        [$dbHost, $dbName, $dbUser, $dbPwd] = self::loadEnvVars();
        $env = [
            'WORDPRESS_DB_HOST' => $dbHost,
            'WORDPRESS_DB_NAME' => $dbName,
            'WORDPRESS_DB_USER' => $dbUser,
            'WORDPRESS_DB_PASSWORD' => $dbPwd,
        ];

        $process = new Process($command, $cliPath, $env);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }

    /**
     * @return void
     */
    private static function initializeWp(): void
    {
        [$dbHost, $dbName, $dbUser, $dbPwd] = self::loadEnvVars();

        self::runWpCliCommand(
            [
                'config',
                'create',
                "--dbname={$dbName}",
                "--dbuser={$dbUser}",
                "--dbpass={$dbPwd}",
                "--dbhost={$dbHost}",
                '--force',
            ]
        );

        self::runWpCliCommand(['config', 'set', 'WP_DEBUG', 'true']);
        self::runWpCliCommand(['config', 'set', 'WP_DEBUG_LOG', 'false']);
        self::runWpCliCommand(['config', 'set', 'WP_DEBUG_DISPLAY', 'true']);
        self::runWpCliCommand(['config', 'set', 'SAVEQUERIES', 'true']);

        self::installDb();
    }

    /**
     * @return array
     */
    private static function loadEnvVars(): array
    {
        $dbHost = getenv('WORDPRESS_DB_HOST');
        $dbName = getenv('WORDPRESS_DB_NAME');
        $dbUser = getenv('WORDPRESS_DB_USER');
        $dbPwd = getenv('WORDPRESS_DB_PASSWORD');

        if (!$dbHost || !$dbName || !$dbUser || !$dbPwd) {
            throw new \Exception('Could not initialize WP: missing env vars.');
        }

        return [$dbHost, $dbName, $dbUser, $dbPwd];
    }

    /**
     * @return void
     */
    private static function installDb(): void
    {
        self::runWpCliCommand(['db', 'reset', '--yes']);

        self::runWpCliCommand(
            [
                'core',
                'install',
                '--url=localhost',
                '--title=Wonolog Test',
                '--admin_user=admin',
                '--admin_password=secret',
                '--admin_email=info@example.com',
            ]
        );
    }
}
