<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Symfony\Component\Process\Process;

class IntegrationTestsExtension implements BeforeFirstTestHook, AfterLastTestHook
{
    /**
     * @return void
     */
    public static function resetDb(): void
    {
        if (!defined('ABSPATH') || !file_exists(ABSPATH . 'wp-config.php')) {
            throw new \Exception('WordPress not installed');
        }

        fwrite(STDOUT, "Resetting WP database...\n");
        static::runWpCliCommand(['db', 'reset', '--yes']);

        fwrite(STDOUT, "Re-installing WP database...\n");
        static::runWpCliCommand(
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

    /**
     * @param array $command
     * @return void
     */
    private static function runWpCliCommand(array $command): void
    {
        static $cliPath;
        $cliPath or $cliPath = (getenv('VENDOR_DIR') ?: '') . '/bin';

        array_unshift($command, "{$cliPath}/wp");
        $command[] = '--path=' . ABSPATH;
        $command[] = '--quiet';
        $command[] = '--skip-plugins';
        $command[] = '--skip-themes';
        $command[] = '--allow-root';

        [$dbHost, $dbName, $dbUser, $dbPwd] = static::loadEnvVars();
        $env = [
            'WORDPRESS_DB_HOST' => $dbHost,
            'WORDPRESS_DB_NAME' => $dbName,
            'WORDPRESS_DB_USER' => $dbUser,
            'WORDPRESS_DB_PASSWORD' => $dbPwd,
        ];

        /** @var string $cliPath */
        $process = new Process($command, $cliPath, $env);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }

    /**
     * @return array{string, string, string, string}
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
    public function executeBeforeFirstTest(): void
    {
        if (!$this->resetWpConfig(false)) {
            return;
        }

        [$dbHost, $dbName, $dbUser, $dbPwd] = static::loadEnvVars();

        fwrite(STDOUT, sprintf("Creating config for WP at %s using %s DB.\n", $dbHost, $dbName));

        static::runWpCliCommand(
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

        static::runWpCliCommand(['config', 'set', 'WP_DEBUG', 'true']);
        static::runWpCliCommand(['config', 'set', 'WP_DEBUG_LOG', 'false']);
        static::runWpCliCommand(['config', 'set', 'WP_DEBUG_DISPLAY', 'true']);
        static::runWpCliCommand(['config', 'set', 'SAVEQUERIES', 'true']);

        static::resetDb();
    }

    /**
     * @return void
     */
    public function executeAfterLastTest(): void
    {
        $this->resetWpConfig(true);
    }

    /**
     * @param bool $end
     * @return bool
     * @throws \Exception
     */
    private function resetWpConfig(bool $end): bool
    {
        $testSuite = $this->phpUnitParam('testsuite');
        $filter = $this->phpUnitParam('filter');
        if ($testSuite !== 'integration' && (stripos($filter, 'integration') === false)) {
            return false;
        }

        if (!defined('ABSPATH')) {
            throw new \Exception('ABSPATH not defined, check bootstrap file.');
        }

        $end or fwrite(STDOUT, sprintf("WP installation found at %s\n", ABSPATH));

        if (file_exists(ABSPATH . 'wp-config.php')) {
            fwrite(STDOUT, sprintf("%sDeleting %s\n", $end ? "\n" : '', ABSPATH . 'wp-config.php'));
            @unlink(ABSPATH . 'wp-config.php');
        }

        return true;
    }

    /**
     * @param string $paramName
     * @return string
     */
    private function phpUnitParam(string $paramName): string
    {
        static $maybeSanitize;
        $maybeSanitize or $maybeSanitize = static function (string $str): string {
            if (
                preg_match('~^(["\'])([^"\']+)?(["\'])$~', $str, $matches)
                && $matches[1] === $matches[3]
            ) {
                return $matches[2];
            }

            return $str;
        };

        global $argv;
        $value = '';
        $i = array_search("--{$paramName}", $argv, true);
        if ($i) {
            return $maybeSanitize($argv[$i + 1] ?? 'yes');
        }
        foreach ($argv as $param) {
            if (preg_match('~--' . $paramName . '(?:=([^\s]+))?~', $param, $matches)) {
                $value = $matches[1] ?? 'yes';
                break;
            }
        }

        return $maybeSanitize($value);
    }
}
