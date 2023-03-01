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
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\HookListener\HttpApiListener;
use Inpsyde\Wonolog\HookListener\MailerListener;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Inpsyde\Wonolog\Processor\WpContextProcessor;
use Inpsyde\Wonolog\WonologFileHandler;
use Inpsyde\Wonolog\Factory;
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
    public function testFallbackHandlerDisabledInOneChannel()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $factory = Factory::new();
        $config = new class($factory) extends Configurator {
            public function __construct(Factory $factory)
            {
                parent::__construct($factory);
            }
        };
        $config
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->pushHandler(new NoopHandler())
            ->removeHandlerFromChannels(NoopHandler::class, Channels::HTTP, Channels::DB)
            ->disableFallbackHandlerForChannels(Channels::DB);

        $config->setup();

        $handlers = $factory->handlersRegistry();

        $debugHandlers = $handlers->findForChannel(Channels::DEBUG);
        $httpHandlers = $handlers->findForChannel(Channels::HTTP);
        $dbHandlers = $handlers->findForChannel(Channels::DB);

        static::assertCount(1, $debugHandlers);
        static::assertCount(1, $httpHandlers);
        static::assertCount(0, $dbHandlers);
        static::assertTrue($debugHandlers[0] instanceof NoopHandler);
        static::assertTrue($httpHandlers[0] instanceof FileHandler);
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testFallbackHandlerEnabledInSpecificChannels()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $factory = Factory::new();
        $config = new class($factory) extends Configurator {
            public function __construct(Factory $factory)
            {
                parent::__construct($factory);
            }
        };
        $config
            ->disableWpContextProcessor()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->disableFallbackHandlerForChannels(Channels::SECURITY, Channels::DB);

        $config->setup();

        $handlersRegistry = $factory->handlersRegistry();

        foreach (Channels::DEFAULT_CHANNELS as $channel) {
            $handlers = $handlersRegistry->findForChannel($channel);
            $notExpecting = $channel === Channels::SECURITY || $channel === Channels::DB;

            static::assertCount($notExpecting ? 0 : 1, $handlers, "For channel {$channel}.");
            if (!$notExpecting) {
                static::assertTrue($handlers[0] instanceof FileHandler);
            }
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testWpContextProcessorEnabledInSpecificChannels()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $factory = Factory::new();
        $config = new class($factory) extends Configurator {
            public function __construct(Factory $factory)
            {
                parent::__construct($factory);
            }
        };
        $config
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->enableWpContextProcessorForChannels(Channels::SECURITY, Channels::DB);

        $config->setup();

        $processorRegistry = $factory->processorsRegistry();

        foreach (Channels::DEFAULT_CHANNELS as $channel) {
            $processors = $processorRegistry->findForChannel($channel);
            $expecting = $channel === Channels::SECURITY || $channel === Channels::DB;

            static::assertCount($expecting ? 1 : 0, $processors, "For channel {$channel}.");
            if ($expecting) {
                static::assertTrue($processors[0] instanceof WpContextProcessor);
            }
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testWpContextProcessorDisabledInSpecificChannels()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        $factory = Factory::new();
        $config = new class($factory) extends Configurator {
            public function __construct(Factory $factory)
            {
                parent::__construct($factory);
            }
        };
        $config
            ->doNotLogPhpErrorsNorExceptions()
            ->disableAllDefaultHookListeners()
            ->disableWpContextProcessorForChannels(Channels::SECURITY, Channels::DB);

        $config->setup();

        $processorRegistry = $factory->processorsRegistry();

        foreach (Channels::DEFAULT_CHANNELS as $channel) {
            $processors = $processorRegistry->findForChannel($channel);
            $notExpecting = $channel === Channels::SECURITY || $channel === Channels::DB;

            static::assertCount($notExpecting ? 0 : 1, $processors, "For channel {$channel}.");
            if (!$notExpecting) {
                static::assertTrue($processors[0] instanceof WpContextProcessor);
            }
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testDefaultHookListenersOptIn()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        Monkey\Actions\expectAdded('phpmailer_init')->once();
        Monkey\Actions\expectAdded('wp_mail_failed')->once();
        Monkey\Actions\expectAdded('http_api_debug')->once();
        Monkey\Actions\expectAdded('wp_loaded')->never();
        Monkey\Actions\expectAdded('shutdown')->never();
        Monkey\Actions\expectAdded('wp_login_failed')->never();
        Monkey\Actions\expectAdded('wp')->never();
        Monkey\Filters\expectAdded('wp_die_ajax_handler')->never();
        Monkey\Filters\expectAdded('wp_die_xmlrpc_handler')->never();
        Monkey\Filters\expectAdded('wp_die_handler')->never();

        Configurator::new()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableWpContextProcessor()
            ->enableDefaultHookListeners(MailerListener::class, HttpApiListener::class)
            ->setup();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testDefaultHookListenersOptOut()
    {
        Monkey\Functions\when('remove_all_actions')->justReturn();
        Monkey\Actions\expectDone(Configurator::ACTION_SETUP)->once();
        Monkey\Actions\expectDone(Configurator::ACTION_LOADED)->once();

        Monkey\Actions\expectAdded('phpmailer_init')->once();
        Monkey\Actions\expectAdded('wp_mail_failed')->once();
        Monkey\Actions\expectAdded('wp_loaded')->once();
        Monkey\Actions\expectAdded('shutdown')->once();
        Monkey\Actions\expectAdded('wp_login_failed')->once();
        Monkey\Filters\expectAdded('wp_die_ajax_handler')->once();
        Monkey\Filters\expectAdded('wp_die_xmlrpc_handler')->once();
        Monkey\Filters\expectAdded('wp_die_handler')->once();
        Monkey\Actions\expectAdded('wp')->never();
        Monkey\Actions\expectAdded('http_api_debug')->never();

        Configurator::new()
            ->doNotLogPhpErrorsNorExceptions()
            ->disableWpContextProcessor()
            ->disableDefaultHookListeners(HttpApiListener::class, QueryErrorsListener::class)
            ->setup();
    }
}
