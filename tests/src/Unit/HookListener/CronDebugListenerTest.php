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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\CronDebugListener;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class CronDebugListenerTest extends TestCase
{

    /**
     * @see CronDebugListener::listenTo()
     */
    public function testListenTo()
    {

        $this->assertSame(
            ['wp_loaded'],
            (new CronDebugListener())->listenTo()
        );
    }

    /**
     * @see CronDebugListener::update()
     */
    public function testUpdate()
    {

        $this->assertInstanceOf(
            NullLog::class,
            (new CronDebugListener())->update([])
        );
    }

    /**
     * @runInSeparateProcess
     * @dataProvider updateRegistersListeners
     * @param int $flags
     * @see CronDebugListener::update()
     *
     */
    public function testUpdateRegistersListeners(int $flags)
    {

        Functions\when('_get_cron_array')
            ->justReturn(
                [
                    ['action_1' => 'do_something'],
                    ['action_2' => 'do_something_else'],
                ]
            );

        Actions\expectAdded('action_1')
            ->twice()
            ->whenHappen(
                static function (callable $callback) {
                    defined('DOING_CRON') or define('DOING_CRON', 1);
                    $callback();
                }
            );

        Actions\expectAdded('action_2')
            ->twice()
            ->whenHappen(
                static function (callable $callback) {
                    defined('DOING_CRON') or define('DOING_CRON', 1);
                    $callback();
                }
            );

        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->with(Info::class)
            ->once()
            ->whenHappen(
                static function (Info $info) {
                    $context = $info->context();

                    static::assertIsArray($context);
                    static::assertArrayHasKey('start', $context);
                    static::assertArrayHasKey('duration', $context);
                    static::assertSame(Channels::DEBUG, $info->channel());
                }
            );

        $listener = new CronDebugListener($flags);

        $this->assertInstanceOf(NullLog::class, $listener->update([]));
    }

    /**
     * @return array<string, array<int>>
     * @see testUpdateRegistersListeners
     */
    public function updateRegistersListeners(): array
    {
        return [
            'is_cron' => [CronDebugListener::IS_CRON],
            'is_cli' => [CronDebugListener::IS_CLI],
            'is_cron_and_cli' => [CronDebugListener::IS_CLI | CronDebugListener::IS_CRON],
        ];
    }

    /**
     * @runInSeparateProcess
     * @see CronDebugListener::__construct()
     */
    public function testConstructorReadsWpCli()
    {

        define('WP_CLI', true);
        $listener = new CronDebugListener();
        $this->assertTrue($listener->isCli());
    }

    /**
     * @runInSeparateProcess
     * @see CronDebugListener::__construct()
     */
    public function testConstructorReadsDoingCron()
    {

        define('DOING_CRON', true);
        $listener = new CronDebugListener();

        $this->assertTrue($listener->isCron());
    }
}
