<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Service\Mail;
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
        $emailTo = $config['to'];
        $emailSubject = $config['subject'];
        $emailBody = $config['message'];

        if (empty($emailTo) || empty($emailSubject) || empty($emailBody)) {
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
