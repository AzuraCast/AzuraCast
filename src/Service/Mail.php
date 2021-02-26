<?php

namespace App\Service;

use App\Entity;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Transport;

class Mail
{
    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected LoggerInterface $logger;

    public function __construct(Entity\Repository\SettingsRepository $settingsRepo, LoggerInterface $logger)
    {
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
    }

    public function isEnabled(): bool
    {
        $settings = $this->settingsRepo->readSettings();

        if (!$settings->getMailEnabled()) {
            return false;
        }

        $requiredSettings = [
            'mailSenderEmail' => $settings->getMailSenderEmail(),
            'mailSmtpHost' => $settings->getMailSmtpHost(),
            'mailSmtpPort' => $settings->getMailSmtpPort(),
        ];

        foreach ($requiredSettings as $settingKey => $setting) {
            if (empty($setting)) {
                $this->logger->error(
                    sprintf(
                        'Cannot send mail via SMTP: required parameter "%s" is missing.',
                        $settingKey
                    )
                );
                return false;
            }
        }

        return true;
    }

    public function createMessage(): Swift_Message
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('SMTP mail is currently disabled for this installation.');
        }

        $settings = $this->settingsRepo->readSettings();

        $message = new Swift_Message();
        $message->setFrom(
            $settings->getMailSenderEmail(),
            $settings->getMailSenderName()
        );
        $message->setCharset('UTF-8');

        return $message;
    }

    public function sendMessage(Swift_Message $message): void
    {
        $transport = $this->getTransport();
        $transport->send($message);
    }

    protected function getTransport(): Swift_Transport
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('SMTP mail is currently disabled for this installation.');
        }

        $settings = $this->settingsRepo->readSettings();

        $transport = new Swift_SmtpTransport(
            $settings->getMailSmtpHost(),
            $settings->getMailSmtpPort()
        );

        if ($settings->getMailSmtpSecure()) {
            $transport->setEncryption('tls');
        }

        if (!empty($settings->getMailSmtpUsername())) {
            $transport->setUsername($settings->getMailSmtpUsername());
            $transport->setPassword($settings->getMailSmtpPassword());
        }

        return $transport;
    }
}
