<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests;

use Monolog\LogRecord;

class TestHandler extends \Monolog\Handler\TestHandler
{
    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        $record->formatted = $this->getFormatter()->format($record);

        $this->write($record);

        return false === $this->bubble;
    }
}