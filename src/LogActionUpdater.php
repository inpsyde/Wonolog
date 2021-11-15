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

use Inpsyde\Wonolog\Data\LogData;

class LogActionUpdater
{
    public const ACTION_LOGGER_ERROR = 'wonolog.logger-error';
    public const FILTER_MASKED_KEYS = 'wonolog.log-masked-keys';
    public const FILTER_CONTEXT = 'wonolog.log-data-context';

    private const SECRET_KEYS = [
        'password',
        'post_password',
        'post-password',
        'postpassword',
        'user_password',
        'user-password',
        'userpassword',
        'client_password',
        'client-password',
        'clientpassword',
        'api_password',
        'api-password',
        'apipassword',
        'auth_password',
        'auth-password',
        'authpassword',
        'secret',
        'user_secret',
        'user-secret',
        'usersecret',
        'client_secret',
        'client-secret',
        'clientsecret',
        'api_secret',
        'api-secret',
        'apisecret',
        'auth_secret',
        'auth-secret',
        'authsecret',
        'auth_key',
        'auth-key',
        'authkey',
        'token',
        'user_token',
        'user-token',
        'usertoken',
        'client_token',
        'client-token',
        'clienttoken',
        'api_token',
        'api-token',
        'apitoken',
        'api_key',
        'api-key',
        'apikey',
        'auth_token',
        'auth-token',
        'authtoken',
    ];

    /**
     * @var Channels
     */
    private $channels;

    /**
     * @var array|null
     */
    private $maskedKeys = null;

    /**
     * @param Channels $channels
     * @return LogActionUpdater
     */
    public static function new(Channels $channels): LogActionUpdater
    {
        return new self($channels);
    }

    /**
     * @param Channels $channels
     */
    private function __construct(Channels $channels)
    {
        $this->channels = $channels;
    }

    /**
     * @param LogData $log
     * @return void
     */
    /**
     * @param LogData $log
     * @return void
     */
    public function update(LogData $log): void
    {
        if (
            !did_action(Configurator::ACTION_LOADED)
            || $log->level() < 1
            || $this->channels->isIgnored($log)
        ) {
            return;
        }

        if ($this->maskedKeys === null) {
            /** @psalm-suppress TooManyArguments */
            $maskedKeys = apply_filters(self::FILTER_MASKED_KEYS, self::SECRET_KEYS, $log);
            is_array($maskedKeys) or $maskedKeys = self::SECRET_KEYS;
            $this->maskedKeys = $maskedKeys;
        }

        $context = [];
        foreach ($log->context() as $key => $value) {
            $context[$key] = in_array($key, $this->maskedKeys, true) ? '***' : $value;
        }

        /** @psalm-suppress TooManyArguments */
        $filteredContext = apply_filters(self::FILTER_CONTEXT, $context, $log);
        is_array($filteredContext) and $context = $filteredContext;

        try {
            $this->channels
                ->logger($log->channel())
                ->log(LogLevel::toPsrLevel($log->level()), $log->message(), $context);
        } catch (\Throwable $throwable) {
            /**
             * Fires when the logger encounters an error.
             *
             * @param LogData $log
             * @param \Exception|\Throwable $throwable
             */
            do_action(self::ACTION_LOGGER_ERROR, $log, $throwable);
        }
    }
}
