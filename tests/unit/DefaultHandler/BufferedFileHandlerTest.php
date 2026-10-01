<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\Tests\FileHandlerTestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class BufferedFileHandlerTest extends FileHandlerTestCase
{
    protected bool $buffered = true;
}
