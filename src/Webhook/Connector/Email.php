<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Service\Mail;
use App\Utilities\Types;
use GuzzleHttp\Client;
use RuntimeException;

final class Email extends AbstractConnector
{
    public function __construct(
        Client $httpClient,
        private readonly Mail $mail
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        if (!$this->mail->isEnabled()) {
            throw new RuntimeException('E-mail delivery is not currently enabled. Skipping webhook delivery...');
        }

        $config = $webhook->getConfig();
        $emailTo = Types::stringOrNull($config['to'], true);
        $emailSubject = Types::stringOrNull($config['subject'], true);
        $emailBody = Types::stringOrNull($config['message'], true);

        if (null === $emailTo || null === $emailSubject || null === $emailBody) {
            throw $this->incompleteConfigException($webhook);
        }

        $email = $this->mail->createMessage();

        foreach (explode(',', $emailTo) as $emailToPart) {
            $email->addTo(trim($emailToPart));
        }

        $vars = [
            'subject' => $emailSubject,
            'body' => $emailBody,
        ];
        $vars = $this->replaceVariables($vars, $np);

        $email->subject($vars['subject']);
        $email->text($vars['body']);

        $this->mail->send($email);
    }
}
