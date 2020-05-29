<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Monolog\Handler\HandlerInterface;

// We want to load this file just once.
// Being loaded by Composer autoload, and being in WordPress context,
// we have to put special care on this.
if (defined(__NAMESPACE__ . '\\LOG')) {
    return;
}

const LOG = 'wonolog.log';
const LOG_PHP_ERRORS = 1;
const USE_DEFAULT_HOOK_LISTENERS = 2;
const USE_DEFAULT_HANDLER = 4;
const USE_DEFAULT_PROCESSOR = 8;
const USE_DEFAULT_ALL = 15;
const USE_DEFAULT_NONE = 0;

/**
 * @param HandlerInterface|NULL $defaultHandler
 * @param int $flags
 * @param int $logHookPriority
 *
 * @return Controller
 */
function bootstrap(
    HandlerInterface $defaultHandler = null,
    int $flags = USE_DEFAULT_ALL,
    int $logHookPriority = 100
): Controller {

    static $controller;
    if ($controller) {
        // This should run once, but we avoid to break return type,
        // just in case it is called more than once
        return $controller;
    }

    $controller = new Controller();
    is_int($flags) or $flags = USE_DEFAULT_NONE;

    if ($flags & LOG_PHP_ERRORS) {
        $controller->logPhpErrors();
    }

    if ($flags & USE_DEFAULT_HOOK_LISTENERS) {
        $controller->useDefaultHookListeners();
    }

    if ($defaultHandler || ($flags & USE_DEFAULT_HANDLER)) {
        $controller->useDefaultHandler($defaultHandler);
    }

    if ($flags & USE_DEFAULT_PROCESSOR) {
        $controller->useDefaultProcessor();
    }

    return $controller->setup($logHookPriority);
}
