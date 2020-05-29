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

namespace Inpsyde\Wonolog\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class FunctionalTestCase extends \PHPUnit\Framework\TestCase
{
    protected $callbacks = [];

    protected $didActions = [];

    private $currentFilter = null;

    protected function setUp(): void
    {
        $stubsPath = getenv('TESTS_PATH') . '/stubs';
        $stubFiles = glob("{$stubsPath}/*.php");
        foreach ($stubFiles as $stubFile) {
            /** @noinspection PhpIncludeInspection */
            require_once $stubFile;
        }

        parent::setUp();
        Monkey\setUp();
        $this->mockHookFunctions();
    }

    protected function tearDown(): void
    {
        $this->callbacks = [];
        $this->didActions = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    private function mockHookFunctions()
    {
        Functions\when('add_action')
            ->alias(
                function (string $hook, callable $callback, int $priority = 10): bool {
                    $this->storeHook($hook, $callback, $priority);

                    return true;
                }
            );

        Functions\when('add_filter')
            ->alias(
                function (string $hook, callable $callback, int $priority = 10): bool {
                    $this->storeHook($hook, $callback, $priority);

                    return true;
                }
            );

        Functions\when('do_action')
            ->alias(
                function (...$args) { // phpcs:ignore
                    $this->executeHook(array_shift($args), $args, false);
                }
            );

        Functions\when('apply_filters')
            ->alias(
                function (...$args) {  // phpcs:ignore
                    return $this->executeHook(array_shift($args), $args, true);
                }
            );

        Functions\when('did_action')
            ->alias(
                function (string $action): bool {
                    return in_array($action, $this->didActions, true);
                }
            );

        Functions\when('current_filter')
            ->alias(
                function (): ?string {
                    return $this->currentFilter;
                }
            );

        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn(false);
    }

    /**
     * @param string $hook
     * @param callable $callable
     * @param int $priority
     */
    private function storeHook(string $hook, callable $callable, int $priority): void
    {
        if (!isset($this->callbacks[$hook][$priority])) {
            $this->callbacks[$hook][$priority] = [];
        }

        $this->callbacks[$hook][$priority][] = $callable;
    }

    /**
     * @param string $hook
     * @param array $args
     * @param bool $filter
     * @return mixed|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    private function executeHook(string $hook, array $args = [], bool $filter = false)
    {
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        $filter or $this->didActions[] = $hook;
        $this->currentFilter = $hook;

        $callbacks = empty($this->callbacks[$hook]) ? [] : $this->callbacks[$hook];

        if (!$callbacks) {
            $this->currentFilter = null;

            return $filter && $args ? reset($args) : null;
        }

        ksort($callbacks);

        array_walk(
            $callbacks,
            static function (array $callbacks) use (&$args, $filter, $hook) {
                array_walk(
                    $callbacks,
                    static function (callable $callback) use (&$args, $filter) {
                        $value = $callback(...$args);
                        $filter and $args[0] = $value;
                    }
                );
            }
        );

        $this->currentFilter = null;

        return $filter && isset($args[0]) ? $args[0] : null;
    }
}
