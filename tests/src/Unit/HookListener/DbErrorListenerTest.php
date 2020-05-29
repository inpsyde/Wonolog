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
use Inpsyde\Wonolog\HookListener\DbErrorListener;
use Brain\Monkey\Actions;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DbErrorListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['EZSQL_ERROR'] = null;
        unset($GLOBALS['EZSQL_ERROR']);
    }

    public function testLogDone()
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $EZSQL_ERROR;
        $EZSQL_ERROR = [['query' => 'This is a SQL query', 'error_str' => 'This is an error']];

        $tester = static function (LogDataInterface $log) use ($EZSQL_ERROR) {
            $context = ['last_query' => 'This is a SQL query', 'errors' => $EZSQL_ERROR];

            static::assertSame(Channels::DB, $log->channel());
            static::assertSame('This is an error', $log->message());
            static::assertEquals($context, $log->context());
        };
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        $listener = new DbErrorListener();

        Actions\expectDone('shutdown')
            ->once()
            ->whenHappen(
                static function () use ($listener, $tester) {
                    $tester($listener->update(func_get_args()));
                }
            );

        do_action($listener->listenTo()[0]);
    }

    public function testLogNotDoneIfNoError()
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $EZSQL_ERROR;
        $EZSQL_ERROR = [];
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        $listener = new DbErrorListener();

        static::assertInstanceOf(NullLog::class, $listener->update([]));
    }
}
