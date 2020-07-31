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

use Monolog\Logger;
use Inpsyde\Wonolog\Data\Log;

/**
 * Handler for PHP core errors, used to log those errors mapping error types to Monolog log levels.
 */
class PhpErrorController
{
    private const ERROR_LEVELS_MAP = [
        E_USER_ERROR => Logger::CRITICAL,
        E_USER_NOTICE => Logger::NOTICE,
        E_USER_WARNING => Logger::WARNING,
        E_USER_DEPRECATED => Logger::NOTICE,
        E_RECOVERABLE_ERROR => Logger::ERROR,
        E_WARNING => Logger::WARNING,
        E_NOTICE => Logger::NOTICE,
        E_DEPRECATED => Logger::NOTICE,
        E_STRICT => Logger::NOTICE,
        E_ERROR => Logger::CRITICAL,
        E_PARSE => Logger::CRITICAL,
        E_CORE_ERROR => Logger::CRITICAL,
        E_CORE_WARNING => Logger::CRITICAL,
        E_COMPILE_ERROR => Logger::CRITICAL,
        E_COMPILE_WARNING => Logger::CRITICAL,
    ];

    private const FATALS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
    ];

    private const SUPER_GLOBALS_KEYS = [
        '_REQUEST',
        '_ENV',
        'GLOBALS',
        '_SERVER',
        '_FILES',
        '_COOKIE',
        '_POST',
        '_GET',
    ];

    /**
     * @var bool
     */
    private $logSilencedErrors;

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
     * @param bool $logSilencedErrors
     * @return PhpErrorController
     */
    public static function new(bool $logSilencedErrors): PhpErrorController
    {
        return new static($logSilencedErrors);
    }

    /**
     * @param bool $logSilencedErrors
     */
    private function __construct(bool $logSilencedErrors)
    {
        $this->logSilencedErrors = $logSilencedErrors;
    }

    /**
     * Error handler.
     *
     * @param int $num
     * @param string $str
     * @param string $file
     * @param int $line
     * @param array|null $context
     * @return bool
     */
    public function onError(
        int $num,
        string $str,
        ?string $file,
        ?int $line,
        ?array $context = null
    ): bool {

        $level = self::ERROR_LEVELS_MAP[$num] ?? Logger::ERROR;
        // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
        if ((error_reporting() === 0) && !$this->logSilencedErrors) {
            return false;
        }
        // phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

        $logContext = [];
        if ($context) {
            $skipKeys = array_merge(array_keys($GLOBALS), self::SUPER_GLOBALS_KEYS);
            $skip = array_fill_keys($skipKeys, '');
            $logContext = array_filter(array_diff_key($context, $skip));
        }

        $logContext['file'] = $file;
        $logContext['line'] = $line;

        // Log the PHP error.
        do_action(
            \Inpsyde\Wonolog\LOG,
            new Log($str, $level, Channels::PHP_ERROR, $logContext)
        );

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
        do_action(
            \Inpsyde\Wonolog\LOG,
            new Log(
                $throwable->getMessage(),
                Logger::CRITICAL,
                Channels::PHP_ERROR,
                [
                    'exception' => get_class($throwable),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => $throwable->getTraceAsString(),
                ]
            )
        );

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

        $error = array_merge(
            ['type' => -1, 'message' => '', 'file' => '', 'line' => 0],
            $lastError
        );

        if (in_array($error['type'], self::FATALS, true)) {
            $this->onError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
