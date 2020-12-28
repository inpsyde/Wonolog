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

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class UnitTestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Monkey\Functions\when('wp_is_stream')->alias(static function (string $path): bool {
            return strpos($path, '://') !== false;
        });

        Monkey\Functions\when('wp_normalize_path')->alias(static function (string $path): string {
            $wrapper = '';
            if (wp_is_stream($path)) {
                [$wrapper, $path] = explode('://', $path, 2);
                $wrapper .= '://';
            }

            $path = preg_replace('|(?<=.)/+|', '/', str_replace('\\', '/', $path));
            ($path[0] === ':') and $path = ucfirst($path);

            return $wrapper . $path;
        });

        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path): bool {
            $path = wp_normalize_path($path);

            return file_exists($path) ? is_dir($path) : mkdir($path, 0777, true);
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
