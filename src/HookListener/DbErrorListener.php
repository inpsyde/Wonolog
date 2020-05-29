<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * At the end of any request looks for database errors and logs them if found.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class DbErrorListener implements ActionListenerInterface
{
    use ListenerIdByClassNameTrait;

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['shutdown'];
    }

    /**
     * Most db errors can't be caught up before request exit.
     * This method runs on shutdown and look if there're errors in `$EZSQL_ERROR`
     * global var and log them if so.
     *
     * @param array $args
     * @return LogDataInterface
     *
     * @wp-hook shutdown
     */
    public function update(array $args): LogDataInterface
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        /** @var array $EZSQL_ERROR */
        global $EZSQL_ERROR;
        if (empty($EZSQL_ERROR)) {
            return new NullLog();
        }

        $errors = $EZSQL_ERROR;
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        $last = end($errors);
        $context = ['last_query' => $last['query'], 'errors' => $errors];

        return new Error($last['error_str'], Channels::DB, $context);
    }
}
