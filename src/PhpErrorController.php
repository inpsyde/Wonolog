<?php

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

    private bool $alreadySetup = false;

    /** @var callable|null */
    private $previousHandler = null;

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
    public static function new(
        int $errorTypes,
        bool $logExceptions,
        bool $logSilencedErrors,
        LogActionUpdater $updater
    ): PhpErrorController {

        return new self($errorTypes, $logExceptions, $logSilencedErrors, $updater);
    }

    /**
     * @param int $errorTypes
     * @param bool $logExceptions
     * @param bool $logSilencedErrors
     * @param LogActionUpdater $updater
     */
    private function __construct(
        private readonly int $errorTypes,
        private readonly bool $logExceptions,
        private readonly bool $logSilencedErrors,
        private readonly LogActionUpdater $updater
    ) {
    }

    /**
     * @return void
     */
    public function setup(): void
    {
        if ($this->alreadySetup) {
            throw new \Exception(__METHOD__ . ' can only be executed once.');
        }
        $this->alreadySetup = true;

        if ($this->logExceptions) {
            $this->previousHandler = set_exception_handler([$this, 'onException']);
        }

        if ($this->errorTypes <= 0) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
        set_error_handler([$this, 'onError'], $this->errorTypes);
        if (self::typesMaskContainsFatals($this->errorTypes)) {
            register_shutdown_function([$this, 'onShutdown']);
        }
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

        // If there was a previous handler, let's call it manually.
        // this make sure that we can throw the exception at the end after having completely
        // reset the handler, obtaining a "transparent" result.
        if ($this->previousHandler) {
            ($this->previousHandler)($throwable);
        }

        // Reset to the default handler and throw, to be transparent
        set_exception_handler(null);
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
            /** @var value-of<PhpErrorController::FATALS> $type */
            $type = $error['type'];
            $message = (string) ($error['message'] ?? '');
            $file = (string) ($error['file'] ?? 'file unknown');
            $line = (int) ($error['line'] ?? -1);
            $this->onError($type, $message, $file, $line);
        }
    }

    /**
     * @return bool
     */
    private function isSilencedError(): bool
    {
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
        // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
        $errorReporting = error_reporting();
        // phpcs:enable WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
        // phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
        if ($errorReporting !== self::PHP_8_SILENCED_ERROR_CODE) {
            return false;
        }

        // If the fixed value returned by `error_reporting()` for silenced error is set in the
        // config we can't really tell the error was suppressed.
        return (int) ini_get('error_reporting') !== $errorReporting;
    }
}
