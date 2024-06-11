<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\SettingsAwareTrait;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class Mail implements MailerInterface
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->readSettings()->getMailEnabled();
    }

    public function createMessage(): Email
    {
        $settings = $this->readSettings();

        $email = new Email();
        $email->from(new Address($settings->getMailSenderEmail(), $settings->getMailSenderName()));

        return $email;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        $this->mailer->send($message, $envelope);
    }
}
