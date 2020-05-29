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

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Handler\DateBasedStreamHandler;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DateBasedStreamHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Functions\when('wp_normalize_path')
            ->alias(
                static function (string $str): string {
                    return str_replace('\\', '/', $str);
                }
            );

        Functions\when('wp_mkdir_p')
            ->alias(
                static function (string $str): string {
                    return filter_var($str, FILTER_SANITIZE_URL) ? $str : '';
                }
            );

        parent::setUp();
    }

    public function testConstructorFailsIfBadFileFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/file name or date format/i');

        new DateBasedStreamHandler('foo', 'd/m/Y');
    }

    public function estConstructorFailsIfBadDateFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/file name or date format/i');

        new DateBasedStreamHandler('{date}.log', 'xxx');
    }

    /**
     * @dataProvider dataProviderForTestStreamHandlerForRecord
     *
     * @param mixed $datetime
     * @param int $timestamp
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function testStreamHandlerForRecord($datetime, int $timestamp)
    {
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration

        $handler = new DateBasedStreamHandler('/etc/logs/{date}.log', 'd/m/Y');

        $streamHandler = $handler->streamHandlerForRecord(compact('datetime'));

        self::assertInstanceOf(StreamHandler::class, $streamHandler);
        self::assertSame(
            '/etc/logs/' . date('d/m/Y', $timestamp) . '.log',
            $streamHandler->getUrl()
        );
    }

    public function testStreamHandlerForRecordWithCallback()
    {
        $fileFormat = static function (array $record): string {
            if (empty($record['channel']) || !is_string($record['channel'])) {
                return '/etc/logs/{date}.log';
            }

            return '/etc/logs/' . strtolower($record['channel']) . '/{date}.log';
        };

        $handler = new DateBasedStreamHandler($fileFormat, 'd/m/Y');

        $timestamp = time();

        $record = [
            'message' => 'Hello',
            'level' => Logger::DEBUG,
            'channel' => 'DEBUG',
            'datetime' => (new \DateTime())->setTimestamp($timestamp),
        ];

        $streamHandler = $handler->streamHandlerForRecord($record);

        self::assertInstanceOf(StreamHandler::class, $streamHandler);
        self::assertSame(
            '/etc/logs/debug/' . date('d/m/Y', $timestamp) . '.log',
            $streamHandler->getUrl()
        );
    }

    /**
     * @return array<array{0:int|string, int}>
     * @see testStreamHandlerForRecord
     */
    public function dataProviderForTestStreamHandlerForRecord(): array
    {
        $time = time();
        $now = new \DateTime('now');
        $weekAgo = new \DateTime();
        $weekAgo->setTimestamp(strtotime('1 week ago'));
        $lastYear = new \DateTime();
        $lastYear->setTimestamp(strtotime('1 year ago'));

        return [
            [$time, $time],
            [(string)$time, $time],
            ['yesterday', strtotime('yesterday')],
            ['2 weeks ago', strtotime('2 weeks ago')],
            [$now, $now->getTimestamp()],
            [$now->format('Y-m-d H:i:s'), $now->getTimestamp()],
            [$now->format('Y-m-d'), $now->getTimestamp()],
            [$now->format('r'), $now->getTimestamp()],
            [$weekAgo, strtotime('1 week ago')],
            [$weekAgo->format('c'), strtotime('1 week ago')],
            [$lastYear->format('c'), $time],
        ];
    }
}
