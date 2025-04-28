<?php

declare( strict_types=1 );

namespace DefaultHandler;

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\DefaultHandler\HandlerFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class HandlerFactoryTest  extends UnitTestCase
{
    public function testReturnsStreamHandlerWhenBufferingAndBubbleAreFalse(): void
    {
        $factory = new HandlerFactory();
        $handler = $factory->make('/path/to/log', Logger::DEBUG, false, false);

        static::assertInstanceOf(StreamHandler::class, $handler);
    }

    public function testReturnsBufferHandlerWhenBufferingIsTrue(): void
    {
        $factory = new HandlerFactory();
        $handler = $factory->make('/path/to/log', Logger::DEBUG, true, false);

        static::assertInstanceOf(BufferHandler::class, $handler);
    }

    public function testReturnsBufferHandlerWhenBubbleIsTrue(): void
    {
        $factory = new HandlerFactory();
        $handler = $factory->make('/path/to/log', Logger::DEBUG, false, true);

        static::assertInstanceOf(StreamHandler::class, $handler);
    }

    public function testReturnsBufferHandlerWhenBufferingAndBubbleAreTrue(): void
    {
        $factory = new HandlerFactory();
        $handler = $factory->make('/path/to/log', Logger::DEBUG, true, true);

        static::assertInstanceOf(BufferHandler::class, $handler);
    }
}
