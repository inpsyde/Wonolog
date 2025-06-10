<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests;

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\LogLevel;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * @phpstan-import-type _RecordType from \Inpsyde\Wonolog\Configurator
 */
class UnitTestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Monkey\Functions\when('wp_is_stream')->alias(static function (string $path): bool {
            return str_contains($path, '://');
        });

        Monkey\Functions\when('wp_normalize_path')->alias(static function (string $path): string {
            $wrapper = '';
            if (wp_is_stream($path)) {
                [$wrapper, $path] = explode('://', $path, 2);
                $wrapper .= '://';
            }

            $path = preg_replace('|(?<=.)/+|', '/', str_replace('\\', '/', $path));
            if (($path[1] ?? '') === ':') {
                $path = ucfirst($path);
            }

            return $wrapper . $path;
        });

        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path): bool {
            $path = wp_normalize_path($path);

            return file_exists($path) ? is_dir($path) : mkdir($path, 0777, true);
        });
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @template T of _RecordType
     *
     * @param string|null $message
     * @param int $number
     * @param array<T> $records
     * @return array<T>
     */
    protected function factoryRecords(
        ?string $message = null,
        int $number = 1,
        array $records = []
    ): array {

        if ($number < 1) {
            return $records;
        }

        if ($message === null) {
            $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'adipiscing', 'elit', 'maximus'];
            shuffle($words);
            $message = implode(' ', array_slice($words, random_int(-2, 2)));
            $message = ucfirst("{$message}.");
        }

        $records[] = (Logger::API < 3) // @phpstan-ignore-line
            ? [
                'message' => $message,
                'level' => LogLevel::DEBUG,
                'channel' => Channels::DEBUG,
                'context' => [],
                'extra' => [],
            ]
            : new LogRecord(
                new \DateTimeImmutable(),
                Channels::DEBUG,
                Level::Debug,
                $message
            );

        return $this->factoryRecords($message, $number - 1, $records);
    }
}
