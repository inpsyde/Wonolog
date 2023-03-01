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
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Inpsyde\Wonolog\HookListener\DbErrorListener;
use Brain\Monkey\Actions;

class DbErrorListenerTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['EZSQL_ERROR'] = null;
        unset($GLOBALS['EZSQL_ERROR']);
    }

    /**
     * @test
     */
    public function testLogDone(): void
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        global $EZSQL_ERROR;
        $EZSQL_ERROR = [['query' => 'This is a SQL query', 'error_str' => 'This is an error']];

        $listener = new DbErrorListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
                    global $EZSQL_ERROR;
                    $context = [
                        'last_wpdb_query' => 'This is a SQL query',
                        'last_wpdb_errors' => $EZSQL_ERROR,
                    ];
                    // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

                    static::assertSame(Channels::DB, $log->channel());
                    static::assertSame('This is an error', $log->message());
                    static::assertEquals($context, $log->context());
                }
            );

        Actions\expectDone('shutdown')
            ->once()
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
                }
            );

        do_action($listener->listenTo()[0]);
    }

    /**
     * @test
     */
    public function testLogNotDoneIfNoError(): void
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $EZSQL_ERROR;
        $EZSQL_ERROR = [];
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        $listener = new DbErrorListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        $listener->update('a', [], $updater);
    }
}
