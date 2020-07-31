<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

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

// We want to load this file just once.
// Being loaded by Composer autoload, and being in WordPress context,
// we have to put special care on this.
use Monolog\Handler\HandlerInterface;

if (defined(__NAMESPACE__ . '\\LOG')) {
    return;
}

const LOG = 'wonolog.log';

/**
 * @param HandlerInterface|null $handler
 * @return Configurator
 */
function bootstrap(?HandlerInterface $handler = null): Configurator
{

    static $config;
    if (!$config) {
        $config = Configurator::new();
        if ($handler) {
            $config = $config
                ->enableDefaultHandler()
                ->pushHandler($handler, DefaultHandler::id());
        }

        add_action('muplugins_loaded', [$config, 'setup'], PHP_INT_MAX);
    }

    return $config;
}
