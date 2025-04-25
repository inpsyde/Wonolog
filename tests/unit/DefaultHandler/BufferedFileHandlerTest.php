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

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Formatter\JsonFormatter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @runTestsInSeparateProcesses
 */
class BufferedFileHandlerTest extends UnitTestCase
{
    use FileHandlerTrait;
}
