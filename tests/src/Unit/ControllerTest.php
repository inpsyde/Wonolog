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

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Controller;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\HookListener\HookListenerInterface;
use Inpsyde\Wonolog\HookListener\HookListenersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ControllerTest extends TestCase
{

    protected function tearDown(): void
    {
        putenv('WONOLOG_DISABLE');
        parent::tearDown();
    }

    public function testSetupDisabledViaEnv()
    {
        putenv('WONOLOG_DISABLE=1');
        Actions\expectDone(Controller::ACTION_SETUP)
            ->never();

        $controller = new Controller();
        $controller->setup();
    }

    public function testSetupDoNothingIfAlreadyDid()
    {

        do_action(Controller::ACTION_SETUP);

        putenv('WONOLOG_DISABLE=1');
        Actions\expectDone(Controller::ACTION_SETUP)
            ->never();

        $controller = new Controller();
        $controller->setup();
    }

    public function testSetupAddHooksOnce()
    {

        Actions\expectDone(Controller::ACTION_SETUP)
            ->once();

        Actions\expectAdded(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(\Mockery::type('callable'), 123, PHP_INT_MAX);

        $levels = [
            '.debug' => 100,
            '.info' => 200,
            '.notice' => 250,
            '.warning' => 300,
            '.error' => 400,
            '.critical' => 500,
            '.alert' => 550,
            '.emergency' => 600,
        ];

        foreach ($levels as $level => $severity) {
            Actions\expectAdded(\Inpsyde\Wonolog\LOG . $level)
                ->once()
                ->with(\Mockery::type('callable'), 123 + (601 - $severity), PHP_INT_MAX);
        }

        Actions\expectAdded('muplugins_loaded')
            ->once()
            ->with([HookListenersRegistry::class, 'initialize'], PHP_INT_MAX);

        Actions\expectDone(Controller::ACTION_LOADED)
            ->once();

        $controller = new Controller();
        $controller->setup(123);
        $controller->setup();
        $controller->setup();
    }

    /**
     * @runInSeparateProcess
     */
    public function testLogPhpErrorsRunOnce()
    {

        Filters\expectAdded(Channels::FILTER_CHANNELS)
            ->once();

        $controller = new Controller();

        static::assertSame($controller, $controller->logPhpErrors());
        static::assertSame($controller, $controller->logPhpErrors());
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultHandlerAddHookOnce()
    {

        Actions\expectAdded(HandlersRegistry::ACTION_REGISTER)
            ->once();

        $controller = new Controller();
        static::assertSame($controller, $controller->useDefaultHandler());
        static::assertSame($controller, $controller->useDefaultHandler());
    }

    public function testUseHandlerAddHooks()
    {

        Actions\expectAdded(HandlersRegistry::ACTION_REGISTER)
            ->twice();

        Actions\expectAdded(Channels::ACTION_LOGGER)
            ->twice();

        $controller = new Controller();
        /** @var HandlerInterface $handler */
        $handler = \Mockery::mock(HandlerInterface::class);

        static::assertSame($controller, $controller->useHandler($handler));
        static::assertSame($controller, $controller->useHandler($handler));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultProcessorAddHookOnce()
    {

        Actions\expectAdded(ProcessorsRegistry::ACTION_REGISTER)
            ->once();

        $controller = new Controller();
        static::assertSame($controller, $controller->useDefaultProcessor());
        static::assertSame($controller, $controller->useDefaultProcessor());
    }

    public function testUseProcessorNoChannelsAddHook()
    {

        Actions\expectAdded(ProcessorsRegistry::ACTION_REGISTER)
            ->twice();

        Actions\expectAdded(Channels::ACTION_LOGGER)
            ->twice();

        $controller = new Controller();
        static::assertSame($controller, $controller->useProcessor('strtolower'));
        static::assertSame($controller, $controller->useProcessor('strtoupper'));
    }

    public function testUseProcessorWithChannelsAddHooks()
    {

        Actions\expectAdded(ProcessorsRegistry::ACTION_REGISTER)
            ->twice();

        Actions\expectAdded(Channels::ACTION_LOGGER)
            ->twice();

        $controller = new Controller();
        static::assertSame($controller, $controller->useProcessor('strtolower', ['foo'], 'foo'));
        static::assertSame($controller, $controller->useProcessor('strtoupper', ['foo'], 'bar'));
    }

    public function testUseProcessorForHandlersAddHooksIfHandlers()
    {

        Actions\expectAdded(ProcessorsRegistry::ACTION_REGISTER)
            ->twice();

        Actions\expectAdded(HandlersRegistry::ACTION_SETUP)
            ->twice();

        $controller = new Controller();
        static::assertSame(
            $controller,
            $controller->useProcessorForHandlers('strtolower', ['foo'], 'foo')
        );
        static::assertSame(
            $controller,
            $controller->useProcessorForHandlers('strtoupper', ['foo'], 'bar')
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultHookListenersAddHookOnce()
    {
        Actions\expectAdded(HookListenersRegistry::ACTION_REGISTER)
            ->once();

        $controller = new Controller();
        static::assertSame($controller, $controller->useDefaultHookListeners());
        static::assertSame($controller, $controller->useDefaultHookListeners());
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseHookListenerAddHook()
    {
        Actions\expectAdded(HookListenersRegistry::ACTION_REGISTER)
            ->twice();

        $controller = new Controller();
        /** @var HookListenerInterface $listener */
        $listener = \Mockery::mock(HookListenerInterface::class);

        static::assertSame($controller, $controller->useHookListener($listener));
        static::assertSame($controller, $controller->useHookListener($listener));
    }
}
