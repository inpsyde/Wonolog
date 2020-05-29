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

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\WpErrorChannel;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpErrorChannelTest extends TestCase
{
    public function testForError()
    {
        $instance = WpErrorChannel::new(\Mockery::mock('WP_Error'));

        self::assertInstanceOf(WpErrorChannel::class, $instance);
    }

    public function testChannelExplicit()
    {
        $instance = WpErrorChannel::new(\Mockery::mock('WP_Error'), 'FOO');
        $channel = $instance->channel();

        self::assertSame('FOO', $channel);
    }

    public function testChannelGuessedDb()
    {
        $error = \Mockery::mock('WP_Error');
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar', 'db_failed']);

        $instance = WpErrorChannel::new($error);
        $channel = $instance->channel();

        self::assertSame(Channels::DB, $channel);
    }

    public function testChannelGuessedHttp()
    {
        $error = \Mockery::mock('WP_Error');
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'bar', 'rest_error']);

        $instance = WpErrorChannel::new($error);
        $channel = $instance->channel();

        self::assertSame(Channels::HTTP, $channel);
    }

    public function testChannelGuessedSecurity()
    {
        $error = \Mockery::mock('WP_Error');
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new($error);
        $channel = $instance->channel();

        self::assertSame(Channels::SECURITY, $channel);
    }

    public function testChannelGuessedFiltered()
    {
        Filters\expectApplied(WpErrorChannel::FILTER_CHANNEL)
            ->once()
            ->with(Channels::SECURITY, \Mockery::type('WP_Error'))
            ->andReturn('BAR');

        $error = \Mockery::mock('WP_Error');
        $error
            ->shouldReceive('get_error_codes')
            ->once()
            ->andReturn(['foo', 'authentication', 'rest_error']);

        $instance = WpErrorChannel::new($error);
        $channel = $instance->channel();

        self::assertSame('BAR', $channel);
    }
}
