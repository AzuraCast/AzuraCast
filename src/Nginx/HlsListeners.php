<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use NowPlaying\Result\Client;
use NowPlaying\Result\Result;
use Psr\Log\LoggerInterface;

final class HlsListeners
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function updateNowPlaying(
        Result $np,
        Station $station,
        bool $includeClients = false
    ): Result {
        if (!$station->getEnableHls()) {
            return $np;
        }

        $hlsStreams = $station->getHlsStreams();
        if (0 === $hlsStreams->count()) {
            $this->logger->error('No HLS streams.');
            return $np;
        }

        $thresholdSecs = $station->getBackendConfig()->getHlsSegmentLength() * 2;
        $timestamp = time() - $thresholdSecs;

        $hlsLogFile = ConfigWriter::getHlsLogFile($station);
        $hlsLogBackup = $hlsLogFile . '.1';

        if (!is_file($hlsLogFile)) {
            $this->logger->error('No HLS log file available.');
            return $np;
        }

        $logContents = file($hlsLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if (is_file($hlsLogBackup) && filemtime($hlsLogBackup) >= $timestamp) {
            $logContents = array_merge(
                $logContents,
                file($hlsLogBackup, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []
            );
        }

        $logContents = array_reverse($logContents);

        $streamsByName = [];
        $clientsByStream = [];
        foreach ($hlsStreams as $hlsStream) {
            $streamsByName[$hlsStream->getName()] = $hlsStream->getIdRequired();
            $clientsByStream[$hlsStream->getName()] = 0;
        }

        $allClients = [];
        $i = 1;
        foreach ($logContents as $logRow) {
            $client = $this->parseRow($logRow, $timestamp);
            if (
                null !== $client
                && isset($clientsByStream[$client->mount])
                && !isset($allClients[$client->uid])
            ) {
                $clientsByStream[$client->mount]++;

                $clientHash = $client->uid;

                $client->uid = (string)$i;
                $client->mount = 'hls_' . $streamsByName[$client->mount];

                $allClients[$clientHash] = $client;
                $i++;
            }
        }

        foreach ($hlsStreams as $hlsStream) {
            $numClients = (int)$clientsByStream[$hlsStream->getName()];
            $hlsStream->setListeners($numClients);
            $this->em->persist($hlsStream);
        }

        $this->em->flush();

        $result = Result::blank();
        $result->listeners->total = $result->listeners->unique = count($allClients);

        $result->clients = ($includeClients)
            ? array_values($allClients)
            : [];

        $this->logger->debug('HLS response', ['response' => $result]);

        return $np->merge($result);
    }

    private function parseRow(
        string $row,
        int $threshold
    ): ?Client {
        try {
            $rowJson = json_decode($row, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        /*
         * Structure:
         * {
         *   "msec": "1656205963.806",
         *   "ua": "Mozilla/5.0 (Windows NT 10.0;...Chrome/102.0.0.0 Safari/537.36",
         *   "ip": "192.168.48.1",
         *   "ip_xff": "",
         *   "uri": "/hls/azuratest_radio/aac_hifi.m3u8"
         * }
         */

        $timestamp = (int)(explode('.', $rowJson['msec'], 2)[0]);
        if ($timestamp < $threshold) {
            return null;
        }

        $ip = (!empty($rowJson['ip_xff'])) ? $rowJson['ip_xff'] : $rowJson['ip'];
        $ua = $rowJson['ua'];

        return new Client(
            uid: md5($ip . '_' . $ua),
            ip: $ip,
            userAgent: $ua,
            connectedSeconds: 1,
            mount: basename($rowJson['uri'], '.m3u8')
        );
    }
}
