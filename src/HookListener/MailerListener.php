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
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;

/**
 * Try to log any error in PHPMailer.
 */
class MailerListener implements ActionListener
{
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
        return ['phpmailer_init', 'wp_mail_failed'];
    }

    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        switch ($hook) {
            case 'phpmailer_init':
                $this->onMailerInit($args, $updater);
                break;
            case 'wp_mail_failed':
                $this->onMailFailed($args, $updater);
                break;
        }
    }

    /**
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     */
    protected function onMailFailed(array $args, LogActionUpdater $updater): void
    {
        $error = $args ? reset($args) : null;
        if ($error instanceof \WP_Error) {
            $updater->update(Log::fromWpError($error, $this->errorLogLevel, Channels::HTTP));
        }
    }

    /**
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     */
    protected function onMailerInit(array $args, LogActionUpdater $updater): void
    {
        $mailer = $args ? reset($args) : null;
        /** @psalm-suppress UndefinedClass */
        if ($mailer instanceof \PHPMailer) {
            $mailer->SMTPDebug = 2;
            $mailer->Debugoutput = static function (string $message) use ($updater): void {
                $updater->update(new Debug($message, Channels::HTTP));
            };
        }
    }
}
