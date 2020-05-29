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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class QueryErrorsListenerTest extends TestCase
{
    public function testLogDone()
    {
        Functions\when('is_404')->justReturn(true);

        Functions\when('add_query_arg')->justReturn('/meh');

        $tester = static function (LogDataInterface $log): void {
            static::assertSame(Channels::HTTP, $log->channel());
            static::assertSame('Error on frontend request for url /meh.', $log->message());
            static::assertSame(
                [
                    'error' => ['404 Page not found'],
                    'query_vars' => ['foo' => 'bar'],
                    'matched_rule' => '/.+/',
                ],
                $log->context()
            );
        };

        /** @var \WP $wp */
        $wp = \Mockery::mock('WP');
        $wp->query_vars = ['foo' => 'bar'];
        $wp->matched_rule = '/.+/';

        $listener = new QueryErrorsListener();

        Actions\expectDone('wp')
            ->whenHappen(
            // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function (...$args) use ($listener, $tester): void {
                    // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                    $tester($listener->update($args));
                }
            );

        do_action($listener->listenTo()[0], $wp);
    }

    public function testLogNotDoneIfWrongArg()
    {
        Functions\when('is_404')->justReturn(true);
        Functions\when('add_query_arg')->justReturn('/meh');

        $wp = new \stdClass();
        $wp->query_vars = ['foo' => 'bar'];
        $wp->matched_rule = '/.+/';

        $listener = new QueryErrorsListener();

        Actions\expectDone('wp')
            ->whenHappen(
                static function () use ($listener): void {
                    $log = $listener->update(func_get_args());
                    static::assertInstanceOf(NullLog::class, $log);
                }
            );

        do_action($listener->listenTo()[0], $wp);
    }
}
