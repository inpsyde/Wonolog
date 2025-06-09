<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\DbErrorListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;

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
        // phpcs:disable Syde.NamingConventions.VariableName.SnakeCaseVar
        global $EZSQL_ERROR;
        $EZSQL_ERROR = [['query' => 'This is a SQL query', 'error_str' => 'This is an error']];
        // phpcs:enable Syde.NamingConventions.VariableName.SnakeCaseVar

        $listener = new DbErrorListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
                    // phpcs:disable Syde.NamingConventions.VariableName.SnakeCaseVar
                    global $EZSQL_ERROR;
                    $context = [
                        'last_wpdb_query' => 'This is a SQL query',
                        'last_wpdb_errors' => $EZSQL_ERROR,
                    ];
                    // phpcs:enable Syde.NamingConventions.VariableName.SnakeCaseVar
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
        // phpcs:disable Syde.NamingConventions.VariableName.SnakeCaseVar
        global $EZSQL_ERROR;
        $EZSQL_ERROR = [];
        // phpcs:enable Syde.NamingConventions.VariableName.SnakeCaseVar

        $listener = new DbErrorListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        $listener->update('a', [], $updater);
    }
}
