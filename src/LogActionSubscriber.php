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

class LogActionSubscriber
{
    /**
     * @var LogActionUpdater
     */
    private $updater;

    /**
     * @var HookLogFactory
     */
    private $factory;

    /**
     * @param LogActionUpdater $updater
     * @param HookLogFactory $factory
     * @return LogActionSubscriber
     */
    public static function new(
        LogActionUpdater $updater,
        HookLogFactory $factory
    ): LogActionSubscriber {

        return new self($updater, $factory);
    }

    /**
     * @param LogActionUpdater $updater
     * @param HookLogFactory $factory
     */
    private function __construct(LogActionUpdater $updater, HookLogFactory $factory)
    {
        $this->updater = $updater;
        $this->factory = $factory;
    }

    /**
     * @wp-hook wonolog.log
     * @wp-hook wonolog.log.debug
     * @wp-hook wonolog.log.info
     * @wp-hook wonolog.log.notice
     * @wp-hook wonolog.log.warning
     * @wp-hook wonolog.log.error
     * @wp-hook wonolog.log.critical
     * @wp-hook wonolog.log.alert
     * @wp-hook wonolog.log.emergency
     */
    public function listen(
        array $hookArguments = [],
        ?string $hookLevel = null,
        ?string $defaultChannel = null
    ): void {

        if (!did_action(Configurator::ACTION_LOADED)) {
            return;
        }

        if ($hookLevel !== null) {
            $hookLevel = LogLevel::normalizeLevel($hookLevel);
        }

        $logs = $this->factory->logsFromHookArguments($hookArguments, $hookLevel, $defaultChannel);
        foreach ($logs as $log) {
            $this->updater->update($log);
        }
    }
}
