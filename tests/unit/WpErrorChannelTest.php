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
        $error->shouldReceive('get_error_data')
            ->with(\Mockery::anyOf('foo', 'bar', 'db_failed'))
            ->andReturn([]);
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar', 'db_failed']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        self::assertSame(Channels::DB, $channel);
    }

    /**
     * @test
     */
    public function testChannelGuessedHttp(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_data')
            ->with(\Mockery::anyOf('foo', 'bar', 'rest_error'))
            ->andReturn([]);
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        self::assertSame(Channels::HTTP, $channel);
    }

    /**
     * @test
     */
    public function testChannelGuessedSecurity(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_data')
            ->with(\Mockery::anyOf('foo', 'authentication', 'rest_error'))
            ->andReturn([]);
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        self::assertSame(Channels::SECURITY, $channel);
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
        $error->shouldReceive('get_error_data')
            ->with(\Mockery::anyOf('foo', 'authentication', 'rest_error'))
            ->andReturn([]);
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        self::assertSame('BAR', $channel);
    }

    /**
     * @test
     */
    public function testChannelByErrorData(): void
    {
        $error = \Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_data')
            ->once()
            ->with('foo')
            ->andReturn([]);
        $error->shouldReceive('get_error_data')
            ->once()
            ->with('bar')
            ->andReturn(['channel' =>'TESTS']);
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar']);

        $instance = WpErrorChannel::new();
        $channel = $instance->channelFor($error);

        self::assertSame('TESTS', $channel);
    }
}
