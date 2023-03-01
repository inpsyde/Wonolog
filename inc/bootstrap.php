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

use Psr\Log\{LoggerInterface, NullLogger};

use function WeCodeMore\earlyAddAction;

// We want to load this file just once.
// Being loaded by Composer autoload, and being in WP context, we have to put special care on this.
if (defined(__NAMESPACE__ . '\\LOG')) {
    return;
}

const LOG = 'wonolog.log';

/**
 * @param string|null $forChannel
 * @return LoggerInterface
 */
function makeLogger(?string $forChannel = null): LoggerInterface
{
    static $loggerFactory, $actionAdded = false;
    /** @var null|callable(?string):LoggerInterface $loggerFactory */
    if ($loggerFactory) {
        return $loggerFactory($forChannel);
    }

    if (!$actionAdded) {
        earlyAddAction(
            Configurator::ACTION_LOADED,
            /** @param callable(?string):LoggerInterface $factory */
            static function (callable $factory) use (&$loggerFactory): void {
                $loggerFactory = $factory;
            },
            PHP_INT_MIN
        );
        $actionAdded = true;
    }

    return new NullLogger();
}

earlyAddAction('muplugins_loaded', [Configurator::new(), 'setup'], PHP_INT_MIN, 0);
