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

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ProcessorsRegistryTest extends TestCase
{
    public function testSameProcessorIsAddedOnce()
    {
        $registry = new ProcessorsRegistry();

        $processorOne = 'strtolower';
        $processorTwo = 'strtoupper';
        $processorThree = 'strrev';

        $registry->addProcessor($processorOne, 'test');
        $registry->addProcessor($processorTwo, 'test');
        $registry->addProcessor($processorThree, 'test');

        self::assertCount(1, $registry);
    }

    public function testHasProcessor()
    {
        $registry = new ProcessorsRegistry();

        $processorOne = 'strtolower';
        $processorTwo = 'strtoupper';
        $processorThree = 'strrev';

        $registry->addProcessor($processorOne, 'a');
        $registry->addProcessor($processorTwo, 'b');
        $registry->addProcessor($processorThree, 'c');

        self::assertTrue($registry->hasProcessor('a'));
        self::assertTrue($registry->hasProcessor('b'));
        self::assertTrue($registry->hasProcessor('c'));
        self::assertFalse($registry->hasProcessor(['a']));
        self::assertFalse($registry->hasProcessor('x'));

        self::assertCount(3, $registry);
    }

    public function testFindByNameCallRegisterOnce()
    {
        $registry = new ProcessorsRegistry();

        Actions\expectDone(ProcessorsRegistry::ACTION_REGISTER)
            ->once()
            ->with($registry);

        self::assertCount(0, $registry);
        self::assertNull($registry->find('foo'));
        self::assertNull($registry->find('bar'));
        self::assertNull($registry->find('baz'));
        self::assertNull($registry->find(1));
    }

    public function testRegisterOnFindByName()
    {
        $registry = new ProcessorsRegistry();

        Actions\expectDone(ProcessorsRegistry::ACTION_REGISTER)
            ->once()
            ->with($registry)
            ->whenHappen(
                static function (ProcessorsRegistry $registry): void {
                    $registry->addProcessor('strtolower', 'a');
                    $registry->addProcessor('strtoupper', 'b');
                }
            );

        self::assertSame('strtolower', $registry->find('a'));
        self::assertSame('strtoupper', $registry->find('b'));
        self::assertSame('strtolower', $registry->find('a'));
        self::assertSame('strtoupper', $registry->find('b'));
        self::assertNull($registry->find('bar'));
        self::assertNull($registry->find('baz'));
        self::assertCount(2, $registry);
    }
}
