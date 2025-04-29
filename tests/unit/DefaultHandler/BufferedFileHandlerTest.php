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

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\DefaultHandler\HandlerFactoryInterface;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\BufferHandler;

/**
 * @runTestsInSeparateProcesses
 */
class BufferedFileHandlerTest extends UnitTestCase
{
    use FileHandlerTrait;

    private function makeSut(?HandlerFactoryInterface $factory = null): FileHandler
    {
        $sut = FileHandler::new($factory);
        $sut->enableBuffering();
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

        $this->assertInstanceOf(BufferHandler::class, $property->getValue($sut));
    }
}
