<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class Mail implements MailerInterface
{
    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected MailerInterface $mailer
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->settingsRepo->readSettings()->getMailEnabled();
    }

    public function createMessage(): Email
    {
        $settings = $this->settingsRepo->readSettings();

        $email = new Email();
        $email->from(new Address($settings->getMailSenderEmail(), $settings->getMailSenderName()));

        return $email;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        $this->mailer->send($message, $envelope);
    }
}
