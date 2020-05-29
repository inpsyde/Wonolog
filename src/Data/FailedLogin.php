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

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;
use Monolog\Logger;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class FailedLogin implements LogDataInterface
{

    public const TRANSIENT_NAME = 'wonolog.failed-login-count';

    /**
     * @var string
     */
    private $username;

    /**
     * Contains the actual IP and the method used to retrieve it
     *
     * @var array
     */
    private $ipData;

    /**
     * @var array
     */
    private $attemptsData;

    /**
     * @var int
     */
    private $attempts;

    /**
     * @param string $username Username used for the failed login attempt
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }

    /**
     * Determine severity of the error based on the number of login attempts in
     * last 5 minutes.
     *
     * @return int
     */
    public function level(): int
    {
        $this->countAttempts(300);

        if ($this->attempts > 2 && $this->attempts <= 100) {
            return Logger::NOTICE;
        }

        if ($this->attempts > 100 && $this->attempts <= 590) {
            return Logger::WARNING;
        }

        if ($this->attempts > 590 && $this->attempts <= 990) {
            return Logger::ERROR;
        }

        if ($this->attempts > 990) {
            return Logger::CRITICAL;
        }

        return 0;
    }

    /**
     * Determine how many failed login attempts comes from the guessed IP.
     * Use a site transient to count them.
     *
     * @param int $ttl transient time to live in seconds
     */
    private function countAttempts(int $ttl = 300): void
    {
        if (isset($this->attempts)) {
            return;
        }

        $this->sniffIp();
        $userIp = $this->ipData[0];

        $attempts = get_site_transient(self::TRANSIENT_NAME);
        is_array($attempts) or $attempts = [];

        // Seems the first time a failed attempt for this IP
        if (!$attempts || !array_key_exists($userIp, $attempts)) {
            $attempts[$userIp] = ['count' => 0, 'last_logged' => 0];
        }

        $attempts[$userIp]['count']++;
        $this->attemptsData = $attempts;

        $count = $attempts[$userIp]['count'];
        $lastLogged = $attempts[$userIp]['last_logged'];

        /**
         * During a brute force attack, logging all the failed attempts
         * can be so expensive to put the server down.
         *
         * So we log:
         *
         * - 3rd attempt
         * - every 20 when total attempts are > 23 && < 100 (23rd, 43rd...)
         * - every 100 when total attempts are > 182 && < 1182 (183rd, 283rd...)
         * - every 200 when total attempts are > 1182 (1183rd, 1383rd...)
         */
        $doLog =
            $count === 3
            || ($count < 100 && ($count - $lastLogged) === 20)
            || ($count < 1000 && ($count - $lastLogged) === 100)
            || (($count - $lastLogged) === 200);

        $doLog and $attempts[$userIp]['last_logged'] = $count;
        set_site_transient(self::TRANSIENT_NAME, $attempts, $ttl);

        $this->attempts = $doLog ? $count : 0;
    }

    /**
     * Try to sniff the current client IP.
     */
    private function sniffIp(): void
    {
        if ($this->ipData) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            $this->ipData = ['127.0.0.1', 'CLI'];

            return;
        }

        $ipServerKeys = [
            'REMOTE_ADDR' => '',
            'HTTP_CLIENT_IP' => '',
            'HTTP_X_FORWARDED_FOR' => '',
        ];

        $ips = array_intersect_key($_SERVER, $ipServerKeys);
        $this->ipData = $ips ? [reset($ips), key($ips)] : ['0.0.0.0', 'Hidden IP'];
    }

    /**
     * @return array
     */
    public function context(): array
    {
        $this->sniffIp();

        return [
            'ip' => $this->ipData[0],
            'ip_from' => $this->ipData[1],
            'username' => $this->username,
        ];
    }

    /**
     * @return string
     */
    public function message(): string
    {
        $this->countAttempts(300);

        if (!$this->attemptsData) {
            return '';
        }

        $this->sniffIp();
        if (!isset($this->attemptsData[$this->ipData[0]]['count'])) {
            return '';
        }

        return sprintf(
            "%d failed login attempts from username '%s' in last 5 minutes",
            (int)$this->attemptsData[$this->ipData[0]]['count'],
            $this->username
        );
    }

    /**
     * @return string
     */
    public function channel(): string
    {
        return Channels::SECURITY;
    }
}
