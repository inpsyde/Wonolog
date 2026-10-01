<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Registry;

use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\LogRecord;

final class ProcessorsRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testSameProcessorIsAddedOnce(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback = (static fn(array|LogRecord $record): array|LogRecord => $record);

        $handlerOne = $callback;
        $handlerTwo = clone $callback;
        $handlerThree = $callback;

        $registry->addProcessor($handlerOne, 'test');
        $registry->addProcessor($handlerTwo, 'test');
        $registry->addProcessor($handlerThree, 'test');

        $this->assertCount(1, $registry);
    }

    /**
     * @test
     */
    public function testChannelSpecificProcessors(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = (static fn(array|LogRecord $record): array|LogRecord => $record);
        $callback2 = (static fn(array|LogRecord $record): array|LogRecord => $record);

        $registry->addProcessor($callback1, 'x', 'A', 'B');
        $registry->addProcessor($callback2, 'y', 'C', 'D');
        $registry->removeProcessorFromChannels('x', 'B');

        $this->assertTrue($registry->hasProcessorForAnyChannel('x'));
        $this->assertTrue($registry->hasProcessorForAnyChannel('y'));
        $this->assertTrue($registry->hasProcessorForChannel('x', 'A'));
        $this->assertFalse($registry->hasProcessorForChannel('x', 'B'));
        $this->assertTrue($registry->hasProcessorForChannel('y', 'C'));
        $this->assertTrue($registry->hasProcessorForChannel('y', 'D'));
        $this->assertFalse($registry->hasProcessorForChannel('x', 'Meh'));
        $this->assertFalse($registry->hasProcessorForChannel('y', 'Meh'));
    }

    /**
     * @test
     */
    public function testAllForLogger(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = (static fn(array|LogRecord $record): array|LogRecord => $record);
        $callback2 = (static fn(array|LogRecord $record): array|LogRecord => $record);

        $registry->addProcessor($callback1, 'x', 'A', 'B');
        $registry->addProcessor($callback2, 'y', 'A', 'C');

        $aProc = $registry->findForChannel('A');
        $bProc = $registry->findForChannel('B');
        $cProc = $registry->findForChannel('C');
        $dProc = $registry->findForChannel('D');

        $this->assertSame([$callback1, $callback2], $aProc);
        $this->assertSame([$callback1], $bProc);
        $this->assertSame([$callback2], $cProc);
        $this->assertSame([], $dProc);
    }

    /**
     * @test
     */
    public function testFindById(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = (static fn(array|LogRecord $record): array|LogRecord => $record);
        $callback2 = (static fn(array|LogRecord $record): array|LogRecord => $record);

        $registry->addProcessor($callback1, 'x');
        $registry->addProcessor($callback2, 'y', 'A', 'C');

        $this->assertSame($callback1, $registry->findById('x'));
        $this->assertSame($callback2, $registry->findById('y'));
        $this->assertNull($registry->findById('z'));
    }
}
