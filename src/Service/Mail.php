<?php

namespace App\Service;

use App\Entity;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

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

    public function createMessage(): Message
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('SMTP mail is currently disabled for this installation.');
        }

        $settings = $this->settingsRepo->readSettings();

        $message = new Message();
        $message->addFrom(
            $settings->getMailSenderEmail(),
            $settings->getMailSenderName()
        );
        $message->setEncoding('UTF-8');

        return $message;
    }

    public function sendMessage(Message $message): void
    {
        $transport = $this->getTransport();
        $transport->send($message);
    }

    protected function getTransport(): TransportInterface
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('SMTP mail is currently disabled for this installation.');
        }

        $settings = $this->settingsRepo->readSettings();

        $options = [
            'name' => $settings->getMailSmtpHost(),
            'host' => $settings->getMailSmtpHost(),
            'port' => $settings->getMailSmtpPort(),
        ];

        if ($settings->getMailSmtpSecure()) {
            $options['connection_config']['ssl'] = 'tls';
        }

        if (!empty($settings->getMailSmtpUsername())) {
            $options['connection_class'] = 'plain';
            $options['connection_config']['username'] = $settings->getMailSmtpUsername();
            $options['connection_config']['password'] = $settings->getMailSmtpPassword();
        }

        return new Smtp(new SmtpOptions($options));
    }
}
