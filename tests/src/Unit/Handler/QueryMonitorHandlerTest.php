<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Inpsyde\Wonolog\Handler\QueryMonitorHandler;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

use function Brain\Monkey\Actions\expectDone;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class QueryMonitorHandlerTest extends TestCase {
    /**
     * @dataProvider get_levels
     */
    public function test_the_handler_raises_the_appropriate_action( $log_level, $expected_qm_action ) {
        $handler = new QueryMonitorHandler();
        $handler->handle( $this->get_record( $log_level ) );

        self::assertTrue( (bool) did_action( $expected_qm_action ) );
    }

    public function test_the_handler_adds_the_message_to_the_log() {
        expectDone( 'qm/debug' )
            ->once()
            ->with( 'This is a test', [ 'foo' => 'bar' ] );

        $handler = new QueryMonitorHandler();
        $handler->handle( $this->get_record( Logger::DEBUG, 'This is a test', [ 'foo' => 'bar' ] ) );
    }

    public function get_levels() {
        return array(
            'Debug level' => array( Logger::DEBUG, 'qm/debug' ),
            'Info level' => array( Logger::INFO, 'qm/info' ),
            'Notice level' => array( Logger::NOTICE, 'qm/notice' ),
            'Warning level' => array( Logger::WARNING, 'qm/warning' ),
            'Error level' => array( Logger::ERROR, 'qm/error' ),
            'Critical level' => array( Logger::CRITICAL, 'qm/critical' ),
            'Alert level' => array( Logger::ALERT, 'qm/alert' ),
            'Emergency level' => array( Logger::EMERGENCY, 'qm/emergency' ),
        );
    }

    /**
     * @param int    $level Level to log.
     * @param string $message Message to log.
     * @param array   $context Context to log.
     *
     * @return array
     */
    protected function get_record( $level = Logger::WARNING, $message = 'test', $context = [] ) {
        return array(
            'message' => (string) $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName( $level ),
            'channel' => 'test',
            'extra' => array(),
        );
    }
}
