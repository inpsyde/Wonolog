<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\DefaultHandler;

use Monolog\Handler\HandlerInterface;

interface HandlerFactoryInterface
{
    public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface;
}
