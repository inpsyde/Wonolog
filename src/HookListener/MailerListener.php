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
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Monolog\Logger;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class MailerListener implements ActionListenerInterface
{
    use ListenerIdByClassNameTrait;

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['phpmailer_init', 'wp_mail_failed'];
    }

    /**
     * @param array $args
     * @return LogDataInterface
     */
    public function update(array $args): LogDataInterface
    {
        switch (current_filter()) {
            case 'phpmailer_init':
                return $this->onMailerInit($args);
            case 'wp_mail_failed':
                return $this->onMailFailed($args);
        }

        return new NullLog();
    }

    /**
     * @param array $args
     * @return LogDataInterface
     */
    private function onMailFailed(array $args): LogDataInterface
    {
        $error = $args ? reset($args) : null;
        if (is_wp_error($error)) {
            return Log::fromWpError($error, Logger::ERROR, Channels::HTTP);
        }

        return new NullLog();
    }

    /**
     * @param array $args
     * @return LogDataInterface
     */
    private function onMailerInit(array $args): LogDataInterface
    {
        $mailer = $args ? reset($args) : null;
        if ($mailer instanceof \PHPMailer) {
            $mailer->SMTPDebug = 2;
            $mailer->Debugoutput = static function (string $message): void {
                // Log the mailer debug message.
                do_action(\Inpsyde\Wonolog\LOG, new Debug($message, Channels::HTTP));
            };
        }

        return new NullLog();
    }
}
