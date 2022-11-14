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

namespace Inpsyde\Wonolog\Data;

trait LogDataTrait
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var array
     */
    private $context;

    /**
     * @param string $message
     * @param string $channel
     * @param array $context
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
