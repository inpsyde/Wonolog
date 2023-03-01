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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\WpErrorChannel;

final class Log implements LogData
{
    use LogDataTrait;

    /**
     * @var array
     */
    private const FILTERS = [
        self::MESSAGE => [
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE,
        ],
        self::LEVEL => FILTER_SANITIZE_NUMBER_INT,
        self::CHANNEL => [
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE,
        ],
        self::CONTEXT => [
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_REQUIRE_ARRAY,
        ],
    ];

    /**
     * @var WpErrorChannel|null
     */
    private static $wpErrorChannel;

    /**
     * @var int
     */
    private $level;

    /**
     * @param array $logData
     * @param string|null $defaultChannel
     * @param int|null $defaultLevel
     * @return Log
     */
    public static function fromArray(
        array $logData,
        ?string $defaultChannel = null,
        ?int $defaultLevel = null
    ): Log {

        if (isset($logData[self::LEVEL])) {
            $logData[self::LEVEL] = LogLevel::normalizeLevel($logData[self::LEVEL]);
        }

        $logData = array_filter(filter_var_array($logData, self::FILTERS) ?: []);
        $message = (string)($logData[self::MESSAGE] ?? 'Unknown error');

        return new self(
            htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false),
            (int)($logData[self::LEVEL] ?? $defaultLevel ?? LogLevel::DEBUG),
            (string)($logData[self::CHANNEL] ?? $defaultChannel ?? Channels::DEBUG),
            (array)($logData[self::CONTEXT] ?? [])
        );
    }

    /**
     * @param \WP_Error $error
     * @param int|null $defaultLevel
     * @param string|null $defaultChannel Channel name
     * @return Log
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public static function fromWpError(
        \WP_Error $error,
        ?int $defaultLevel = LogLevel::NOTICE,
        ?string $defaultChannel = null
    ): Log {

        $level = LogLevel::normalizeLevel($defaultLevel) ?? LogLevel::NOTICE;
        $message = (string)$error->get_error_message();
        $context = (array)($error->get_error_data() ?: []);

        self::$wpErrorChannel or self::$wpErrorChannel = WpErrorChannel::new();

        $channel = self::$wpErrorChannel->channelFor($error) ?? $defaultChannel ?? Channels::DEBUG;

        // Raise level for "guessed" channels
        if ($channel === Channels::SECURITY && $level < LogLevel::ERROR) {
            $level = LogLevel::ERROR;
        } elseif ($channel !== Channels::DEBUG && $level < LogLevel::NOTICE) {
            $level = LogLevel::NOTICE;
        }

        return new self((string)$message, $level, $channel, $context);
    }

    /**
     * @param \Throwable $throwable
     * @param int|null $level
     * @param string $channel
     * @param array $context
     * @return Log
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public static function fromThrowable(
        \Throwable $throwable,
        ?int $level = LogLevel::ERROR,
        string $channel = Channels::DEBUG,
        array $context = []
    ): Log {

        $level = LogLevel::normalizeLevel($level) ?? LogLevel::ERROR;
        $channel or $channel = Channels::DEBUG;

        $context['throwable'] = [
            'class' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTrace(),
        ];

        return new self($throwable->getMessage(), $level, $channel, $context);
    }

    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array $context
     */
    public function __construct(
        string $message = '',
        int $level = LogLevel::DEBUG,
        string $channel = Channels::DEBUG,
        array $context = []
    ) {

        $this->level = $level;
        $this->message = $message;
        $this->channel = $channel;
        $this->context = $context;
    }

    /**
     * @param LogData $log
     *
     * @return Log
     */
    public function merge(LogData $log): Log
    {
        $logData = [
            self::MESSAGE => $log->message(),
            self::LEVEL => $log->level(),
            self::CHANNEL => $log->channel(),
            self::CONTEXT => $log->context(),
        ];

        return $this->mergeArray($logData);
    }

    /**
     * @param array $logData
     *
     * @return Log
     */
    public function mergeArray(array $logData): Log
    {
        $base = [
            self::MESSAGE => $this->message(),
            self::LEVEL => $this->level(),
            self::CHANNEL => $this->channel(),
            self::CONTEXT => $this->context(),
        ];

        $logData = array_replace($base, $logData);

        return self::fromArray($logData);
    }

    /**
     * @return int
     */
    public function level(): int
    {
        return $this->level;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Log
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function with(string $key, $value): Log
    {
        if (!array_key_exists($key, self::FILTERS)) {
            throw new \InvalidArgumentException('Invalid log key.');
        }

        return $this->mergeArray([$key => $value]);
    }
}
