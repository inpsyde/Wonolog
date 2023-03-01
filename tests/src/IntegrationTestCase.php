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

namespace Inpsyde\Wonolog\Tests;

use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\Registry\HandlersRegistry;

abstract class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param Configurator $configurator
     * @return void
     */
    abstract protected function bootstrapWonolog(Configurator $configurator): void;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('ABSPATH')) {
            return;
        }

        require_once ABSPATH . 'wp-includes/plugin.php';

        add_filter(HandlersRegistry::FILTER_BUFFER_HANDLER, '__return_false');

        add_action(
            Configurator::ACTION_SETUP,
            function (Configurator $configurator): void {
                $this->bootstrapWonolog($configurator);
            }
        );

        require_once ABSPATH . 'wp-config.php';
    }
}
