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
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * Listens to 'http_api_debug' hook to discover and log WP HTTP API errors.
 *
 * Differentiate between WP cron requests and other HTTP requests.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class HttpApiListener implements ActionListenerInterface
{
    use ListenerIdByClassNameTrait;

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
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['http_api_debug'];
    }

    /**
     * Log HTTP cron requests.
     *
     * @param array $args
     * @return LogDataInterface
     *
     * @wp-hook http_api_debug
     */
    public function update(array $args): LogDataInterface
    {
        /**
         * @var \WP_Error|array|null $response
         * @var string|null $context
         * @var string|null $class
         * @var array|null $httpArgs
         * @var string|null $url
         */
        [$response, $context, $class, $httpArgs, $url] = array_pad($args, 5, null);

        if (!$response || !$context || !$class || !is_string($context) || !is_string($class)) {
            return new NullLog();
        }

        $isWpError = is_wp_error($response);
        if (!$isWpError && !is_array($response)) {
            return new NullLog();
        }

        ($httpArgs && is_array($httpArgs)) or $httpArgs = [];
        ($url && is_string($url)) or $url = '';

        if ($isWpError || $this->isError($response, (array)$httpArgs)) {
            if ($isWpError) {
                $response = ['response' => ['message' => $response->get_error_message()]];
            }

            return $this->logHttpError($response, $context, $class, $httpArgs, $url);
        }

        if ($this->isCron($response, $url)) {
            /** @var array $response */
            return $this->logCron($response, $context, $class, $httpArgs, $url);
        }

        return new NullLog();
    }

    /**
     * @param array|\WP_Error $response
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
     * @param array|\WP_Error $response
     * @param string $url
     * @return bool
     */
    private function isCron(array $response, string $url): bool
    {
        return
            is_array($response)
            && basename((string)parse_url($url, PHP_URL_PATH)) === 'wp-cron.php';
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
    private function logCron(
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
     *
     * @return Error
     */
    private function logHttpError(
        array $data,
        string $context,
        string $class,
        array $args,
        string $url
    ): Error {

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
            (is_array($data) && !empty($data['headers'])) and $logContext['headers'] = $data['headers'];
        }

        return new Error($msg, Channels::HTTP, $logContext);
    }
}
