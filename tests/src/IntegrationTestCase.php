<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests;

use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Registry\HandlersRegistry;
use PHPUnit\Framework\TestCase;
use Syde\WpPhpUnitIntegration\WpTestEnv;

abstract class IntegrationTestCase extends TestCase
{
    /**
     * @param Configurator $configurator
     * @return void
     */
    abstract protected function bootstrapWonolog(Configurator $configurator): void;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(WpTestEnv::class)) {
            $this->setUpWithWpPhpunitIntegration();

            return;
        }

        $this->setUpLegacy();
    }

    /**
     * @return void
     */
    private function setUpWithWpPhpunitIntegration(): void
    {
        WpTestEnv::addEarlyFilter(
            HandlersRegistry::FILTER_BUFFER_HANDLER,
            static function (): bool {
                return false;
            }
        );

        WpTestEnv::addEarlyAction(
            Configurator::ACTION_SETUP,
            function (Configurator $configurator): void {
                $this->bootstrapWonolog($configurator);
            }
        );

        WpTestEnv::addEarlyAction(
            LogActionUpdater::ACTION_LOGGER_ERROR,
            static function (mixed $log, mixed $throwable): void {
                //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_print_backtrace
                debug_print_backtrace();
                //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
                if ($throwable instanceof \Throwable) {
                    fwrite(STDOUT, "\nThere was an error: " . $throwable->getMessage() . "\n");
                }
            },
            10,
            2
        );

        WpTestEnv::load();
    }

    /**
     * @return void
     */
    private function setUpLegacy(): void
    {
        if (!defined('ABSPATH')) {
            return;
        }

        require_once ABSPATH . 'wp-includes/plugin.php';

        add_filter(HandlersRegistry::FILTER_BUFFER_HANDLER, '__return_false');

        add_action(
            Configurator::ACTION_SETUP,
            function (Configurator $configurator): void {
                $this->bootstrapWonolog($configurator);
            }
        );

        add_action(
            LogActionUpdater::ACTION_LOGGER_ERROR,
            static function (mixed $log, mixed $throwable): void {
                //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_print_backtrace
                debug_print_backtrace();
                //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
                if ($throwable instanceof \Throwable) {
                    fwrite(STDOUT, "\nThere was an error: " . $throwable->getMessage() . "\n");
                }
            },
            10,
            2
        );

        require_once ABSPATH . 'wp-config.php';
    }
}
