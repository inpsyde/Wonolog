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

use Brain\Monkey\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\WpErrorChannel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class WpErrorChannelTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testChannelGuessedDb(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->allows('get_error_data')
            ->with(\Mockery::anyOf('foo', 'bar', 'db_failed'))
            ->andReturn([]);
        $error
            ->expects('get_error_codes')
            ->andReturn(['foo', 'bar', 'db_failed']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        static::assertSame(Channels::DB, $channel);
    }

    /**
     * @test
     */
    public function testChannelGuessedHttp(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->allows('get_error_data')
            ->with(\Mockery::anyOf('foo', 'bar', 'rest_error'))
            ->andReturn([]);
        $error
            ->expects('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        static::assertSame(Channels::HTTP, $channel);
    }

    /**
     * @test
     */
    public function testChannelGuessedSecurity(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->allows('get_error_data')
            ->with(\Mockery::anyOf('foo', 'authentication', 'rest_error'))
            ->andReturn([]);
        $error
            ->expects('get_error_codes')
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        static::assertSame(Channels::SECURITY, $channel);
    }

    /**
     * @test
     */
    public function testChannelGuessedFiltered(): void
    {
        Filters\expectApplied(WpErrorChannel::FILTER_CHANNEL)
            ->once()
            ->with(Channels::SECURITY, \Mockery::type('WP_Error'))
            ->andReturn('BAR');

        $error = \Mockery::mock('WP_Error');
        $error->allows('get_error_data')
            ->with(\Mockery::anyOf('foo', 'authentication', 'rest_error'))
            ->andReturn([]);
        $error
            ->expects('get_error_codes')
            ->once()
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        static::assertSame('BAR', $channel);
    }

    /**
     * @test
     */
    public function testChannelByErrorData(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->expects('get_error_data')->with('foo')->andReturn([]);
        $error->expects('get_error_data')->with('bar')->andReturn(['channel' =>'TESTS']);
        $error->allows('get_error_codes')->andReturn(['foo', 'bar']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        static::assertSame('TESTS', $channel);
    }
}
