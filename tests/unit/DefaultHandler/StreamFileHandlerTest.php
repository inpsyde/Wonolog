<?php

declare( strict_types=1 );

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\DefaultHandler\HandlerFactoryInterface;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\StreamHandler;

/**
 * @runTestsInSeparateProcesses
 */
class StreamFileHandlerTest  extends UnitTestCase
{
    use FileHandlerTrait;

    private function makeSut(HandlerFactoryInterface $factory = null): FileHandler
    {
        $sut = FileHandler::new($factory);
        $sut->disableBuffering();
        return $sut;
    }

    public function testBuffering(): void
    {
        $this->setupFolders();
        $sut = $this->makeSut();
        $reflection = new \ReflectionClass($sut);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($sut);
        $property = $reflection->getProperty('handler');

        $this->assertInstanceOf(StreamHandler::class, $property->getValue($sut));
    }
}
