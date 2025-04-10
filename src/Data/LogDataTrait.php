<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Data;

trait LogDataTrait
{
    private string $message;

    private string $channel;

    /**
     * @var mixed[]
     */
    private array $context;

    /**
     * @param string $message
     * @param string $channel
     * @param mixed[] $context
     */
    public function __construct(string $message, string $channel, array $context = [])
    {
        $this->message = $message;
        $this->channel = $channel;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function channel(): string
    {
        return $this->channel;
    }
}
