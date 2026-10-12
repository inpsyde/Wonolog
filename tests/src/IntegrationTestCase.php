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
                if ($throwable instanceof \Throwable) {
                    fwrite(STDOUT, "\nThere was an error: " . $throwable->getMessage() . "\n");
                }
            },
            10,
            2
        );

        WpTestEnv::load();
    }
}
