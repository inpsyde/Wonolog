<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\MonologUtils;
use Inpsyde\Wonolog\Processor\WpContextProcessor;
use Inpsyde\Wonolog\RecordFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\LogRecord;

class WpContextProcessorTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('get_option')->justReturn();
    }

    /**
     * @test
     */
    public function testAdminBeforeInitSingleSite(): void
    {
        Functions\when('is_admin')->justReturn(true);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => true,
                    'doing_rest' => false,
                    'multisite' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function testFrontendBeforeInitSingleSite(): void
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'doing_rest' => false,
                    'multisite' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    private function whenRecordIsTestMessage(string $type): string
    {
        return 'when $record is of type ' . $type;
    }

    /**
     * @test
     */
    public function testAdminAfterInitSingleSite(): void
    {
        do_action('init');

        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $expected = [
            'wp' => [
                'doing_cron' => false,
                'doing_ajax' => false,
                'is_admin' => true,
                'doing_rest' => false,
                'user_id' => 1,
                'multisite' => false,
            ],
        ];

        $processedRecord = $processor([]);
        $this->assertEquals(
            $expected,
            $processedRecord['extra'],
            $this->whenRecordIsTestMessage('array')
        );
        if (MonologUtils::version() < 3) {
            return;
        }
        $processedRecord = $processor($this->buildLogRecord());
        $this->assertEquals(
            $expected,
            $processedRecord->extra,
            $this->whenRecordIsTestMessage(LogRecord::class)
        );
    }

    /**
     * @return LogRecord
     */
    private static function buildLogRecord(): LogRecord
    {
        return RecordFactory::createRecordV3(
            'foo log msg',
            LogLevel::DEBUG,
            'default'
        );
    }

    /**
     * @test
     */
    public function testRestAfterInitSingleSite(): void
    {
        do_action('init');

        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('http://example.com/wp-json/foo/bar');
        Functions\when('set_url_scheme')
            ->alias(
                static function (string $str): string {
                    return str_replace('http://', 'https://', $str);
                }
            );

        $processor = WpContextProcessor::new();

        $expected = [
            'wp' => [
                'doing_cron' => false,
                'doing_ajax' => false,
                'is_admin' => false,
                'doing_rest' => true,
                'user_id' => 1,
                'multisite' => false,
            ],
        ];

        $processedRecord = $processor([]);
        $this->assertEquals(
            $expected,
            $processedRecord['extra'],
            $this->whenRecordIsTestMessage('array')
        );
        if (MonologUtils::version() < 3) {
            return;
        }
        $processedRecord = $processor($this->buildLogRecord());
        $this->assertEquals(
            $expected,
            $processedRecord->extra,
            $this->whenRecordIsTestMessage(LogRecord::class)
        );
    }

    /**
     * @test
     */
    public function testFrontendAfterParseRequestSingleSite(): void
    {
        do_action('init');
        do_action('parse_request');

        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');

        $processor = WpContextProcessor::new();

        $expected = [
            'wp' => [
                'doing_cron' => false,
                'doing_ajax' => false,
                'is_admin' => false,
                'doing_rest' => false,
                'user_id' => 1,
                'multisite' => false,
            ],
        ];

        $processedRecord = $processor([]);
        $this->assertEquals(
            $expected,
            $processedRecord['extra'],
            $this->whenRecordIsTestMessage('array')
        );
        if (MonologUtils::version() < 3) {
            return;
        }
        $processedRecord = $processor($this->buildLogRecord());
        $this->assertEquals(
            $expected,
            $processedRecord->extra,
            $this->whenRecordIsTestMessage(LogRecord::class)
        );
    }

    public function testFrontendAfterParseRequestMultiSite(): void
    {
        do_action('init');
        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');
        Functions\when('ms_is_switched')->justReturn(true);
        Functions\when('get_current_blog_id')->justReturn(2);
        Functions\when('get_current_network_id')->justReturn(3);

        $processor = WpContextProcessor::new();

        $expected = [
            'wp' => [
                'doing_cron' => false,
                'doing_ajax' => false,
                'is_admin' => false,
                'doing_rest' => false,
                'user_id' => 1,
                'multisite' => true,
                'ms_switched' => true,
                'site_id' => 2,
                'network_id' => 3,
            ],
        ];

        $processedRecord = $processor([]);
        $this->assertEquals(
            $expected,
            $processedRecord['extra'],
            $this->whenRecordIsTestMessage('array')
        );
        if (MonologUtils::version() < 3) {
            return;
        }
        $processedRecord = $processor($this->buildLogRecord());
        $this->assertEquals(
            $expected,
            $processedRecord->extra,
            $this->whenRecordIsTestMessage(LogRecord::class)
        );
    }
}
