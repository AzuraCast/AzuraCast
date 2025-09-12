<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Simulcasting;
use App\Entity\Station;
use GuzzleHttp\Client;

final class Centrifugo
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function isSupported(): bool
    {
        return !$this->environment->isTesting();
    }

    public function publishToStation(Station $station, mixed $message, array $triggers): void
    {
        $this->send([
            'method' => 'publish',
            'params' => [
                'channel' => $this->getChannelName($station),
                'data' => $this->buildStationMessage($message, $triggers),
            ],
        ]);
    }

    public function buildStationMessage(mixed $message, array $triggers = []): array
    {
        return [
            'np' => $message,
            'triggers' => $triggers,
            'current_time' => time(),
        ];
    }

    private function send(array $body): void
    {
        try {
            $response = $this->client->post(
                'http://localhost:6025/api',
                [
                    'json' => $body,
                ]
            );
            error_log('Centrifugo: Successfully sent data, response: ' . $response->getBody());
        } catch (\Exception $e) {
            error_log('Centrifugo: Error sending data: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getChannelName(Station $station): string
    {
        return 'station:' . $station->short_name;
    }

    public function publishSimulcastStatus(Station $station, Simulcasting $simulcasting): void
    {
        $data = [
            'method' => 'publish',
            'params' => [
                'channel' => 'simulcast:' . $station->short_name,
                'data' => [
                    'simulcast' => $this->serializeSimulcasting($simulcasting),
                    'current_time' => time(),
                ],
            ],
        ];
        
        error_log('Centrifugo: Publishing simulcast status for station ' . $station->short_name . ' to channel: simulcast:' . $station->short_name . ' with data: ' . json_encode($data));
        
        $this->send($data);
    }

    private function serializeSimulcasting(Simulcasting $simulcasting): array
    {
        return [
            'id' => $simulcasting->getId(),
            'name' => $simulcasting->getName(),
            'status' => $simulcasting->getStatus()->value,
            'error_message' => $simulcasting->getErrorMessage()
        ];
    }
}
