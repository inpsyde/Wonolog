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

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;

/**
 * Listens to 'http_api_debug' hook to discover and log WP HTTP API errors.
 *
 * Differentiate between WP cron requests and other HTTP requests.
 */
final class HttpApiListener implements ActionListener
{
    private const HTTP_SUCCESS_CODES = [
        200,
        201,
        202,
        203,
        204,
        205,
        206,
        207,
        300,
        301,
        302,
        303,
        304,
        305,
        306,
        307,
        308,
    ];

    /**
     * @var int
     */
    private $errorLogLevel;

    /**
     * @param int $errorLogLevel
     */
    public function __construct(int $errorLogLevel = LogLevel::ERROR)
    {
        $this->errorLogLevel = LogLevel::normalizeLevel($errorLogLevel) ?? LogLevel::ERROR;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['http_api_debug'];
    }

    /**
     * Log HTTP cron requests.
     *
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook http_api_debug
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        /**
         * @var mixed|null $response
         * @var mixed|null $context
         * @var mixed|null $class
         * @var mixed|null $httpArgs
         * @var mixed|null $url
         */
        [$response, $context, $class, $httpArgs, $url] = array_pad($args, 5, null);

        if (!$response || !$context || !$class || !is_string($context) || !is_string($class)) {
            return;
        }

        $errorMessage = '';
        if ($response instanceof \WP_Error) {
            $errorMessage = $response->get_error_message();
        }

        if (!($response instanceof \WP_Error) && !is_array($response)) {
            return;
        }

        is_array($httpArgs) or $httpArgs = [];
        is_string($url) or $url = '';

        /** @var LogData|null $log */
        $log = null;
        if (($response instanceof \WP_Error) || $this->isError($response, $httpArgs)) {
            if (($response instanceof \WP_Error)) {
                $response = ['response' => ['message' => $errorMessage]];
            }

            $log = $this->logHttpError($response, $context, $class, $httpArgs, $url);
        } elseif ($this->isCron($url)) {
            $log = $this->logCron($response, $context, $class, $httpArgs, $url);
        }

        $log and $updater->update($log);
    }

    /**
     * @param array $response
     * @param array $httpArgs
     * @return bool
     */
    private function isError(array $response, array $httpArgs = []): bool
    {
        $code = $response['response']['code'] ?? null;

        if (!$code || !is_numeric($code)) {
            return true;
        }

        if (array_key_exists('blocking', $httpArgs) && !$httpArgs['blocking']) {
            return false;
        }

        return !in_array((int)$code, self::HTTP_SUCCESS_CODES, true);
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isCron(string $url): bool
    {
        return basename((string)parse_url($url, PHP_URL_PATH)) === 'wp-cron.php';
    }

    /**
     * Log HTTP cron requests.
     *
     * @param array $data
     * @param string $context
     * @param string $class
     * @param array $args
     * @param string $url
     * @return Debug
     */
    protected function logCron(
        array $data,
        string $context,
        string $class,
        array $args = [],
        string $url = ''
    ): Debug {

        $logContext = [
            'transport' => $class,
            'context' => $context,
            'query_args' => $args,
            'url' => $url,
        ];

        if (!empty($data['headers'])) {
            $logContext['headers'] = $data['headers'];
        }

        return new Debug('Cron request', Channels::DEBUG, $logContext);
    }

    /**
     * Log any error for HTTP API.
     *
     * @param array $data
     * @param string $context
     * @param string $class
     * @param array $args
     * @param string $url
     * @return LogData
     */
    protected function logHttpError(
        array $data,
        string $context,
        string $class,
        array $args,
        string $url
    ): LogData {

        $msg = 'WP HTTP API Error';
        $response = isset($data['response']) && is_array($data['response'])
            ? array_replace(['message' => '', 'code' => '', 'body' => ''], $data['response'])
            : ['message' => '', 'code' => '', 'body' => ''];

        if ($response['message'] && is_string($response['message'])) {
            $msg .= ': ' . $response['message'];
        }

        $logContext = [
            'transport' => $class,
            'context' => $context,
            'query_args' => $args,
            'url' => $url,
        ];

        if ($response['body'] && is_string($response['body'])) {
            $logContext['response_body'] = strlen($response['body']) <= 300
                ? $response['body']
                : substr($response['body'], 0, 300) . '...';
        }

        $code = $response['code'] ?? null;
        if ($code && is_scalar($code)) {
            $msg .= " - Response code: {$response[ 'code' ]}";
            empty($data['headers']) or $logContext['headers'] = $data['headers'];
        }

        return new Log($msg, $this->errorLogLevel, Channels::HTTP, $logContext);
    }
}
