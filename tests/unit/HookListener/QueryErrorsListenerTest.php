<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class QueryErrorsListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogDone(): void
    {
        Functions\when('is_404')->justReturn(true);

        Functions\when('add_query_arg')->justReturn('/meh');

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
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
                }
            );

        if (!class_exists(\WP::class)) {
            eval('class WP { public $query_vars = []; public $matched_rule = null; }');
        }
        $wp = new \WP();
        $wp->query_vars = ['foo' => 'bar'];
        $wp->matched_rule = '/.+/';

        $listener = new QueryErrorsListener();

        Actions\expectDone('wp')
            ->whenHappen(
                static function (mixed ...$args) use ($listener, $updater): void {
                    $listener->update('a', $args, $updater);
                }
            );

        do_action($listener->listenTo()[0], $wp);
    }

    /**
     * @test
     */
    public function testLogNotDoneIfWrongArg(): void
    {
        Functions\when('is_404')->justReturn(true);
        Functions\when('add_query_arg')->justReturn('/meh');

        $wp = new \stdClass();
        $wp->query_vars = ['foo' => 'bar'];
        $wp->matched_rule = '/.+/';

        $listener = new QueryErrorsListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        Actions\expectDone('wp')
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
                }
            );

        do_action($listener->listenTo()[0], $wp);
    }
}
