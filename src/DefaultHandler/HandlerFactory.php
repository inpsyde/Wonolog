<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\DefaultHandler;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;

class HandlerFactory implements HandlerFactoryInterface
{
    public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
    {
        $streamBuffer = $buffering || $bubble;
        $handler = new StreamHandler($logFilePath, $level, $streamBuffer, null, true);
        return $buffering
            ? new BufferHandler($handler, 0, $level, $bubble)
            : $handler;
    }
}
