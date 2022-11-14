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

namespace Inpsyde\Wonolog\DefaultHandler;

class LogsFolder
{
    /**
     * @var string|null
     */
    private static $folder;

    /**
     * @param string|null $customFolder
     * @return string|null
     */
    public static function determineFolder(?string $customFolder = null): ?string
    {
        if (self::$folder && (!$customFolder || ($customFolder === self::$folder))) {
            return self::$folder;
        }

        /**
         * Let's disable error reporting: too much file operations which might fail, nothing can log
         * them, and package could be fully functional even if failures happen.
         * Silence looks like best option here.
         *
         * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
         */
        set_error_handler('__return_true');

        try {
            if ($customFolder) {
                return wp_mkdir_p($customFolder)
                    ? static::maybeCreateHtaccess($customFolder)
                    : null;
            }

            $folder = static::maybeDetermineFolderByConstant();

            if ($folder === null) {
                $uploadDir = static::uploadsBaseDir();
                $uploadDir and $folder = (string)wp_normalize_path("{$uploadDir}/wonolog");
            }

            if ($folder === null && defined('WP_CONTENT_DIR')) {
                $content = trailingslashit(WP_CONTENT_DIR);
                $folder = wp_normalize_path("{$content}/wonolog");
            }

            if (!$folder || !wp_mkdir_p($folder)) {
                return null;
            }

            self::$folder = static::maybeCreateHtaccess($folder);

            return self::$folder;
        } catch (\Throwable $throwable) {
            return null;
        } finally {
            restore_error_handler();
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
     * @return string|null
     */
    private static function maybeCreateHtaccess(string $folder): ?string
    {
        $targetDir = rtrim((string)wp_normalize_path($folder), '/');
        if (!$targetDir) {
            return null;
        }

        $isDir = is_dir($folder);
        if (!$isDir || file_exists("{$targetDir}/.htaccess")) {
            return $isDir ? "{$targetDir}/" : null;
        }

        $contentDir = defined('WP_CONTENT_DIR')
            ? rtrim(wp_normalize_path(WP_CONTENT_DIR), '/')
            : null;

        $uploadDir = static::uploadsBaseDir();

        if (!$contentDir && !$uploadDir) {
            return $targetDir;
        }

        if (($targetDir === $contentDir) || ($targetDir === $uploadDir)) {
            $targetDir .= '/wonolog';
            if (!wp_mkdir_p($targetDir)) {
                return null;
            }
        }

        if ($uploadDir && $contentDir && (strpos($uploadDir, $contentDir) === 0)) {
            $uploadDir = null;
        }

        $targetDir .= '/';
        $contentDir and $contentDir .= '/';
        $uploadDir and $uploadDir .= '/';

        // We will create .htaccess only if target dir is inside one of the two directories we
        // assume are publicly accessible.
        if (
            (!$contentDir || (strpos($targetDir, $contentDir) !== 0))
            && (!$uploadDir || (strpos($targetDir, $uploadDir) !== 0))
        ) {
            return $targetDir;
        }

        $htaccess = <<<'HTACCESS'
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Deny from all
</IfModule>
HTACCESS;
        file_put_contents("{$targetDir}.htaccess", $htaccess);

        return $targetDir;
    }

    /**
     * @return string|null
     */
    private static function uploadsBaseDir(): ?string
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
    private static function maybeDetermineFolderByConstant(): ?string
    {
        $maybeLogFiles = [];

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && is_string(WP_DEBUG_LOG)) {
            $isBool = filter_var(WP_DEBUG_LOG, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            ($isBool === null) and $maybeLogFiles[] = WP_DEBUG_LOG;
        }

        if (defined('ERRORLOGFILE') && ERRORLOGFILE && is_string(ERRORLOGFILE)) {
            $maybeLogFiles[] = ERRORLOGFILE;
        }

        /** @var string $maybeLogFile */
        foreach ($maybeLogFiles as $maybeLogFile) {
            $dirByConstant = dirname($maybeLogFile);
            if ($dirByConstant && $dirByConstant !== '.') {
                $folder = wp_normalize_path((string)trailingslashit($dirByConstant) . 'wonolog');

                return (string)$folder;
            }
        }

        return null;
    }
}
