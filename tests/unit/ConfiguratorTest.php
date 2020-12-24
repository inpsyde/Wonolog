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

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\WonologFileHandler;
use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\Registry\HandlersRegistry;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NoopHandler;

class ConfiguratorTest extends UnitTestCase
{
    /**
     * @test
     * @runInSeparateProcess
     */
    public function testConfiguratorDisabledViaEnvVar(): void
    {
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->never();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->never();

        putenv('WONOLOG_DISABLE=yes');

        Configurator::new()->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testConfiguratorDisabledViaConstant()
    {
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->never();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->never();

        define('WONOLOG_DISABLE', 'yes');
        Configurator::new()->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testConfiguratorDisabledViaFilter()
    {
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->never();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->never();
        Monkey\Filters\expectApplied(Configurator::FILTER_DISABLE)->andReturn(true);

        putenv('WONOLOG_DISABLE=no');
        define('WONOLOG_DISABLE', false);

        Configurator::new()->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testConfiguratorDisabledBecauseNoHandlers()
    {
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->never();

        Configurator::new()
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->disableFallbackHandler()
            ->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testEnabledViaCustomHandler()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        Configurator::new()
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->disableFallbackHandler()
            ->pushHandler(\Mockery::mock(HandlerInterface::class))
            ->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testGivenDefaultHandlerDisabledInOneChannel()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $config = Configurator::new()
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->pushHandler(new NoopHandler())
            ->removeHandlerFromChannels(NoopHandler::class, Channels::HTTP, Channels::DB)
            ->disableFallbackHandlerForChannels(Channels::DB);

        $config->setup();

        /** @var HandlersRegistry $handlers */
        $handlers = (function (): Factory {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->factory;
        })->call($config)->handlersRegistry();

        $debugHandlers = $handlers->findForChannel(Channels::DEBUG);
        $httpHandlers = $handlers->findForChannel(Channels::HTTP);
        $dbHandlers = $handlers->findForChannel(Channels::DB);

        static::assertCount(1, $debugHandlers);
        static::assertCount(1, $httpHandlers);
        static::assertCount(0, $dbHandlers);
        static::assertInstanceOf(NoopHandler::class, $debugHandlers[0]);
        static::assertInstanceOf(WonologFileHandler::class, $httpHandlers[0]);
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testGivenDefaultHandlerEnabledInSpecificChannels()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $config = Configurator::new()
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->enableFallbackHandlerForChannels(Channels::DEBUG)
            ->disableFallbackHandlerForChannels(Channels::SECURITY, Channels::DB);

        $config->setup();

        /** @var HandlersRegistry $handlersRegistry */
        $handlersRegistry = (function (): Factory {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->factory;
        })->call($config)->handlersRegistry();

        foreach (Channels::DEFAULT_CHANNELS as $channel) {
            $handlers = $handlersRegistry->findForChannel($channel);
            $notExpecting = $channel === Channels::SECURITY || $channel === Channels::DB;

            static::assertCount($notExpecting ? 0 : 1, $handlers, "For channel {$channel}.");
            if (!$notExpecting) {
                static::assertInstanceOf(WonologFileHandler::class, $handlers[0]);
            }
        }
    }
}
