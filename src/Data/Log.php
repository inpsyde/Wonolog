<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\LogLevel;
use Monolog\Logger;

/**
 * Generic log data object.
 *
 * It is a value object used to pass data to wonolog.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class Log implements LogDataInterface
{
    use LogDataTrait;

    /**
     * @var array
     */
    private static $filters = [
        self::MESSAGE => FILTER_SANITIZE_STRING,
        self::LEVEL => FILTER_SANITIZE_NUMBER_INT,
        self::CHANNEL => FILTER_SANITIZE_STRING,
        self::CONTEXT => ['filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY],
    ];

    /**
     * @var int
     */
    private $level;

    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array $context
     */
    public function __construct(
        string $message = '',
        int $level = Logger::DEBUG,
        string $channel = Channels::DEBUG,
        array $context = []
    ) {

        $this->level = $level;
        $this->message = $message;
        $this->channel = $channel;
        $this->context = $context;
    }

    /**
     * @param \WP_Error $error
     * @param string|int $level A string representing the level, e.g. `"NOTICE"`
     *                          or an integer, very likely via Logger constants,
     *                          e.g. `Logger::NOTICE`
     * @param string $channel Channel name
     *
     * @return Log
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public static function fromWpError(
        \WP_Error $error,
        $level = Logger::NOTICE,
        $channel = ''
    ): Log {

        $logLevel = LogLevel::instance();
        $level = $logLevel->check_level($level)
            ?: Logger::NOTICE;

        $message = $error->get_error_message();
        $context = $error->get_error_data()
            ?: [];

        if ($channel) {
            return new static($message, $level, $channel, $context);
        }

        $channel = WpErrorChannel::new($error)
            ->channel();

        // Raise level for "guessed" channels
        if ($channel === Channels::SECURITY && $level < Logger::ERROR) {
            $level = Logger::ERROR;
        } elseif ($channel !== Channels::DEBUG && $level < Logger::WARNING) {
            $level = Logger::WARNING;
        }

        return new static($message, $level, $channel, $context);
    }

    /**
     * @param \Throwable $throwable
     * @param int|string $level A string representing the level, e.g. `"NOTICE"`
     *                          or an integer, very likely via Logger constants,
     *                          e.g. `Logger::NOTICE`
     * @param string $channel
     * @param array $context
     *
     * @return Log
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public static function fromThrowable(
        \Throwable $throwable,
        $level = Logger::ERROR,
        string $channel = Channels::DEBUG,
        array $context = []
    ): Log {

        $logLevel = LogLevel::instance();
        $level = $logLevel->check_level($level)
            ?: Logger::ERROR;

        $channel or $channel = Channels::DEBUG;

        $context['throwable'] = [
            'class' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTrace(),
        ];

        return new static($throwable->getMessage(), $level, $channel, $context);
    }

    /**
     * @param LogDataInterface $log
     *
     * @return Log
     */
    public function merge(LogDataInterface $log): Log
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

        $logData = (array) shortcode_atts($base, $logData);

        return self::fromArray($logData);
    }

    /**
     * @inheritdoc
     */
    public function level(): int
    {
        return $this->level;
    }

    /**
     * @param array $logData
     *
     * @return Log
     */
    public static function fromArray(array $logData): Log
    {
        $defaults = [
            self::MESSAGE => 'Unknown error',
            self::LEVEL => Logger::DEBUG,
            self::CHANNEL => Channels::DEBUG,
            self::CONTEXT => [],
        ];

        $logLevel = LogLevel::instance();
        $levels = Logger::getLevels();

        if (isset($logData[self::LEVEL]) && is_string($logData[self::LEVEL])) {
            $logData[self::LEVEL] = $logLevel->check_level($logData[self::LEVEL], $levels);
        }

        $logData = (array) filter_var_array($logData, self::$filters);
        $logData = (array) array_filter($logData);

        $data = array_merge($defaults, $logData);

        return new static(
            $data[self::MESSAGE],
            $data[self::LEVEL],
            $data[self::CHANNEL],
            $data[self::CONTEXT]
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Log
     * @throws \InvalidArgumentException
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function with(string $key, $value): Log
    {
        if (! array_key_exists($key, self::$filters)) {
            throw new \InvalidArgumentException('Invalid Log key.');
        }

        return $this->mergeArray([$key => $value]);
    }
}
