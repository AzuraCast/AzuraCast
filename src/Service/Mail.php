<?php

namespace App\Service;

use App\Entity;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class Mail implements MailerInterface
{
    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected MailerInterface $mailer;

    public function __construct(Entity\Repository\SettingsRepository $settingsRepo, MailerInterface $mailer)
    {
        $this->settingsRepo = $settingsRepo;
        $this->mailer = $mailer;
    }

    public function isEnabled(): bool
    {
        $settings = $this->settingsRepo->readSettings();
        return $settings->getMailEnabled();
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
