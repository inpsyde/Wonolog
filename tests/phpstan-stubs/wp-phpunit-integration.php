<?php

// phpcs:ignoreFile

declare(strict_types=1);

// PHPStan-only stub for syde/wp-phpunit-integration (deliberately not a composer.json dependency).
// Never require this at runtime, and keep it out of tests/stubs/ -- doing either would make
// class_exists(WpTestEnv::class) always true, breaking the legacy Docker-based test flavor.

namespace Syde\WpPhpUnitIntegration;

class Bootstrap
{
    public static function init(
        string $packageRootPath,
        BootstrapLifecycle $bootstrapLifecycle = new BootstrapLifecycle(),
    ): void {
    }
}

readonly class BootstrapLifecycle
{
    public function __construct(
        private ?\Closure $setup = null,
        private ?\Closure $load = null,
        private ?\Closure $cleanup = null,
    ) {
    }
}

class WpTestEnv
{
    public static function setup(): void
    {
    }

    public static function load(): void
    {
    }

    public static function cleanup(): void
    {
    }

    /**
     * @param list<string> $args
     */
    public static function runWpCliCommand(array $args): void
    {
    }

    public static function addEarlyAction(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1,
    ): void {
    }

    public static function addEarlyFilter(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1,
    ): void {
    }
}
