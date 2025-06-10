<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use PHPMailer\PHPMailer;

/**
 * Try to log any error in PHPMailer.
 */
class MailerListener implements ActionListener
{
    private int $errorLogLevel;
    private int $smtpDebugLevel;

    /**
     * @param int $errorLogLevel
     * @param int $smtpDebugLevel
     */
    public function __construct(
        int $errorLogLevel = LogLevel::ERROR,
        int $smtpDebugLevel = PHPMailer\SMTP::DEBUG_SERVER,
    ) {

        $this->errorLogLevel = LogLevel::normalizeLevel($errorLogLevel) ?? LogLevel::ERROR;
        $this->smtpDebugLevel = min(
            max(PHPMailer\SMTP::DEBUG_OFF, $smtpDebugLevel),
            PHPMailer\SMTP::DEBUG_LOWLEVEL
        );
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
            $updater->update(Log::fromWpError($error, $this->errorLogLevel, Channels::NETWORK));
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
        if (!($mailer instanceof PHPMailer\PHPMailer)) {
            return;
        }

        $mailer->SMTPDebug = $this->smtpDebugLevel;
        $mailer->Debugoutput = static function (string $message) use ($updater): void {
            $updater->update(new Debug($message, Channels::NETWORK));
        };
    }
}
