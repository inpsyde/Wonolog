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

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\StreamHandler;

class DefaultHandler implements HandlerInterface, ProcessableHandlerInterface
{
    /**
     * @var string|null
     */
    private $folder;

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var int|null
     */
    private $minLevel;

    /**
     * @var bool
     */
    private $bubble = true;

    /**
     * @var HandlerInterface|null
     */
    private $handler;

    /**
     * @var string|null
     */
    private $logFilePath;

    /**
     * @return string
     */
    public static function id(): string
    {
        static $id;
        $id or $id = 'wonolog-std-' . bin2hex(random_bytes(3));

        return (string)$id;
    }

    /**
     * @return DefaultHandler
     */
    public static function new(): DefaultHandler
    {
        return new self();
    }

    /**
     * Empty on purpose.
     */
    private function __construct()
    {
    }

    /**
     * @param string $folder
     * @return $this
     */
    public function withFolder(string $folder): DefaultHandler
    {
        $this->folder = wp_normalize_path($folder);

        return $this;
    }

    /**
     * @param string $filename
     * @return DefaultHandler
     */
    public function withFilename(string $filename): DefaultHandler
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @param string $format
     * @param string $extension
     * @return static
     */
    public function withDateBasedFileFormat(
        string $format,
        string $extension = 'log'
    ): DefaultHandler {

        $date = date($format);
        if (!$date) {
            return $this;
        }

        $this->filename = ($extension && $extension !== '.')
            ? "{$date}." . ltrim($extension, '.')
            : $date;

        return $this;
    }

    /**
     * @param int $level
     * @return DefaultHandler
     */
    public function withMinimumLevel(int $level): DefaultHandler
    {
        $this->minLevel = LogLevel::normalizeLevel($level);

        return $this;
    }

    /**
     * @return DefaultHandler
     */
    public function enableBubbling(): DefaultHandler
    {
        $this->bubble = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableBubbling(): DefaultHandler
    {
        $this->bubble = false;

        return $this;
    }

    /**
     * @param array $record
     * @return bool
     */
    public function handle(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->handle($record);
    }

    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->isHandling($record);
    }

    /**
     * @param array $records
     * @return void
     */
    public function handleBatch(array $records): void
    {
        $this->ensureHandler();

        $this->handler->handleBatch($records);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->ensureHandler();

        $this->handler->close();
    }

    /**
     * @param callable(array):array|\Monolog\Processor\ProcessorInterface $callback
     * @return HandlerInterface
     */
    public function pushProcessor(callable $callback): HandlerInterface
    {
        $this->ensureHandler();
        if ($this->handler instanceof ProcessableHandlerInterface) {
            return $this->handler->pushProcessor($callback);
        }

        return $this;
    }

    /**
     * @return callable(array):array
     */
    public function popProcessor(): callable
    {
        if ($this->handler instanceof ProcessableHandlerInterface) {
            return $this->handler->popProcessor();
        }

        return new NullProcessor();
    }

    /**
     * @return string
     */
    public function logFilePath(): string
    {
        if ($this->logFilePath) {
            return $this->logFilePath;
        }

        if ($this->folder === null) {
            $this->folder = $this->maybeDetermineFolderByConstant();
        }

        if ($this->folder === null) {
            $uploadDir = $this->uploadsBaseDir();
            $uploadDir and $this->folder = (string)wp_normalize_path($uploadDir . '/wonolog');
        }

        if ($this->folder === null && defined('WP_CONTENT_DIR') && WP_CONTENT_DIR) {
            $content = (string)trailingslashit(WP_CONTENT_DIR);
            $this->folder = (string)wp_normalize_path($content . 'logs/wonolog');
        }

        if (!$this->folder || !wp_mkdir_p($this->folder)) {
            throw new \Exception('Could not determine or create valid log file path.');
        }

        $this->folder = (string)trailingslashit($this->maybeCreateHtaccess($this->folder));

        $logFileName = $this->filename ?? (date('Y/m/d') . '.log');
        $logFilePath = $this->folder . ltrim($logFileName, '/\\');

        $logFileFir = dirname($logFilePath);
        if (!$logFileFir || $logFileFir === '.') {
            throw new \Exception('Could not determine valid log file path.');
        }

        if (!wp_mkdir_p($logFileFir)) {
            throw new \Exception('Could not create valid log file path.');
        }

        if (!is_writable($logFileFir)) {
            throw new \Exception('Could not obtain valid log file path: not writable.');
        }

        return (string)wp_normalize_path($logFilePath);
    }

    /**
     * @return void
     *
     * @psalm-assert HandlerInterface $this->handler
     */
    private function ensureHandler(): void
    {
        if ($this->handler) {
            return;
        }

        try {
            $this->logFilePath = $this->logFilePath();
            $this->handler = new StreamHandler(
                $this->logFilePath,
                $this->minLevel ?? LogLevel::defaultMinLevel(),
                $this->bubble
            );
        } catch (\Throwable $throwable) {
            $this->handler = new NullHandler();
        }
    }

    /**
     * When the log root folder is publicly accessible, it means logs can be exposed and that can
     * easily be a privacy-leakage issue and/or a security threat.
     *
     * In that case we try to write a .htaccess file to prevent access to logs.
     * This guarantees **nothing**, because .htaccess can be ignored depending which web server is
     * used and which is its configuration, but at least we tried.
     * The right thing to do is to configure log folder to be outside web root and that is also
     * highly recommended in documentation.
     *
     * @param string $folder
     * @return string
     */
    private function maybeCreateHtaccess(string $folder): string
    {
        if (!is_dir($folder) || file_exists("{$folder}/.htaccess")) {
            return $folder;
        }

        $targetDir = rtrim((string)wp_normalize_path($folder), '/');
        if (!$targetDir) {
            return '';
        }

        $contentDir = defined('WP_CONTENT_DIR')
            ? rtrim((string)wp_normalize_path(WP_CONTENT_DIR), '/')
            : null;

        $uploadDir = $this->uploadsBaseDir();
        if ($uploadDir && $contentDir && (strpos($uploadDir, $contentDir) === 0)) {
            $uploadDir = null;
        }

        if (!$contentDir && !$uploadDir) {
            return $targetDir;
        }

        if (($targetDir === $contentDir) || ($targetDir === $uploadDir)) {
            $targetDir .= '/wonolog';
            if (!wp_mkdir_p($targetDir)) {
                return '';
            }
        }

        // We will create .htaccess only if target dir is inside one of the two directories we
        // assume are publicly accessible.
        if (
            (!$contentDir || (strpos($targetDir, $contentDir) !== 0))
            && (!$uploadDir || (strpos($targetDir, $uploadDir) !== 0))
        ) {
            return $targetDir;
        }

        /**
         * Let's disable error reporting: too much file operations which might fail, nothing can log
         * them, and package is fully functional even if failing happens.
         * Silence looks like best option here.
         *
         * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
         */
        set_error_handler('__return_true');
        $htaccess = <<<'HTACCESS'
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Deny from all
</IfModule>
HTACCESS;
        // phpcs:disable WordPress.PHP.NoSilencedErrors
        @file_put_contents("{$targetDir}/.htaccess", $htaccess);
        // phpcs:enable WordPress.PHP.NoSilencedErrors
        restore_error_handler();

        return $targetDir;
    }

    /**
     * @return string|null
     */
    private function uploadsBaseDir(): ?string
    {
        /** @var array{string|null}|null $uploadsBaseDir */
        static $uploadsBaseDir;
        if (isset($uploadsBaseDir)) {
            return $uploadsBaseDir[0];
        }

        $uploads = (array)wp_upload_dir(null, false);
        if (empty($uploads['error']) && !empty($uploads['basedir'])) {
            $baseDir = (string)$uploads['basedir'];
            $uploadsBaseDir = [rtrim((string)wp_normalize_path($baseDir), '/') ?: null];

            return $uploadsBaseDir[0];
        }

        $uploadsBaseDir = [null];

        return null;
    }

    /**
     * @return string|null
     */
    private function maybeDetermineFolderByConstant(): ?string
    {
        $maybeLogFiles = [];

        if (defined('ERRORLOGFILE') && ERRORLOGFILE && is_string(ERRORLOGFILE)) {
            $maybeLogFiles[] = ERRORLOGFILE;
        }

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && is_string(WP_DEBUG_LOG)) {
            $isBool = filter_var(WP_DEBUG_LOG, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            ($isBool === null) and $maybeLogFiles[] = WP_DEBUG_LOG;
        }

        /** @var string $maybeLogFile */
        foreach ($maybeLogFiles as $maybeLogFile) {
            // phpcs:disable WordPress.PHP.NoSilencedErrors
            $dirByConstant = @dirname($maybeLogFile);
            // phpcs:enable WordPress.PHP.NoSilencedErrors
            if ($dirByConstant && $dirByConstant !== '.') {
                $folder = wp_normalize_path((string)trailingslashit($dirByConstant) . 'wonolog');

                return (string)$folder;
            }
        }

        return null;
    }
}
