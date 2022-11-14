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

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;

/**
 * Handler for PHP core errors, used to log those errors mapping error types to Monolog log levels.
 */
class PhpErrorController
{
    private const ERROR_LEVELS_MAP = [
        E_USER_ERROR => LogLevel::CRITICAL,
        E_USER_NOTICE => LogLevel::NOTICE,
        E_USER_WARNING => LogLevel::WARNING,
        E_USER_DEPRECATED => LogLevel::NOTICE,
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_WARNING => LogLevel::WARNING,
        E_NOTICE => LogLevel::NOTICE,
        E_DEPRECATED => LogLevel::NOTICE,
        E_STRICT => LogLevel::NOTICE,
        E_ERROR => LogLevel::CRITICAL,
        E_PARSE => LogLevel::CRITICAL,
        E_CORE_ERROR => LogLevel::CRITICAL,
        E_CORE_WARNING => LogLevel::CRITICAL,
        E_COMPILE_ERROR => LogLevel::CRITICAL,
        E_COMPILE_WARNING => LogLevel::CRITICAL,
    ];

    private const FATALS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
    ];

    private const PHP_8_SILENCED_ERROR_CODE = E_ERROR
        | E_CORE_ERROR
        | E_COMPILE_ERROR
        | E_USER_ERROR
        | E_RECOVERABLE_ERROR
        | E_PARSE;

    /**
     * @var bool
     */
    private $logSilencedErrors;

    /**
     * @var LogActionUpdater
     */
    private $updater;

    /**
     * @param int $errorTypes
     * @return bool
     */
    public static function typesMaskContainsFatals(int $errorTypes): bool
    {
        foreach (self::FATALS as $errorType) {
            if (($errorType & $errorTypes) === $errorType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Throwable $throwable
     * @param string|null $message
     * @return Log
     */
    public static function factoryThrowableLog(\Throwable $throwable, ?string $message = null): Log
    {
        return new Log(
            $message ?? $throwable->getMessage(),
            LogLevel::CRITICAL,
            Channels::PHP_ERROR,
            [
                'exception' => get_class($throwable),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ]
        );
    }

    /**
     * @param bool $logSilencedErrors
     * @param LogActionUpdater $updater
     * @return PhpErrorController
     */
    public static function new(bool $logSilencedErrors, LogActionUpdater $updater): PhpErrorController
    {
        return new self($logSilencedErrors, $updater);
    }

    /**
     * @param bool $logSilencedErrors
     * @param LogActionUpdater $updater
     */
    private function __construct(bool $logSilencedErrors, LogActionUpdater $updater)
    {
        $this->logSilencedErrors = $logSilencedErrors;
        $this->updater = $updater;
    }

    /**
     * @param int $num
     * @param string $str
     * @param string|null $file
     * @param int|null $line
     * @return bool
     */
    public function onError(int $num, string $str, ?string $file, ?int $line): bool
    {
        if (!$this->logSilencedErrors && $this->isSilencedError()) {
            return false;
        }

        $level = self::ERROR_LEVELS_MAP[$num] ?? LogLevel::ERROR;
        $logContext = [];
        $logContext['file'] = $file;
        $logContext['line'] = $line;

        // Log the PHP error.
        $this->updater->update(new Log($str, $level, Channels::PHP_ERROR, $logContext));

        return false;
    }

    /**
     * Uncaught exception handler.
     *
     * @param \Throwable $throwable
     */
    public function onException(\Throwable $throwable): void
    {
        // Log the PHP exception.
        $this->updater->update(static::factoryThrowableLog($throwable));

        // after logging let's reset handler and throw the exception
        restore_exception_handler();
        throw $throwable;
    }

    /**
     * Checks for a fatal error, work-around for `set_error_handler` not working with fatal errors.
     */
    public function onShutdown(): void
    {
        $lastError = error_get_last();
        if (!$lastError) {
            return;
        }

        $error = array_replace(
            ['type' => -1, 'message' => '', 'file' => '', 'line' => null],
            $lastError
        );

        if (in_array($error['type'], self::FATALS, true)) {
            $this->onError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * @return bool
     */
    private function isSilencedError(): bool
    {
        // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
        $errorReporting = error_reporting();
        // phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

        /**
         * Prior to PHP 8, calling error_reporting() inside a custom error handler would return
         * 0 if the error was suppressed via @, but as of PHP 8.0.0, it returns a fixed value.
         * We can say the error is silenced if `error_reporting()` above returns that value and
         * if that value is different from what is set in ini.
         * @see https://www.php.net/manual/en/language.operators.errorcontrol.php
         */
        if (PHP_MAJOR_VERSION >= 8) {
            if ($errorReporting !== self::PHP_8_SILENCED_ERROR_CODE) {
                return false;
            }

            // If the fixed value returned by `error_reporting()` for silenced error is set in the
            // config we can't really tell the error was suppressed.
            return (int)ini_get('error_reporting') !== $errorReporting;
        }

        return $errorReporting === 0;
    }
}
