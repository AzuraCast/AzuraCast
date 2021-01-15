<?php

namespace App\Service;

use App\Entity;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class LastFm
{
    public const API_BASE_URL = 'https://ws.audioscrobbler.com/2.0/';

    protected Client $httpClient;

    protected LoggerInterface $logger;

    protected ?string $apiKey = null;

    public function __construct(
        Client $client,
        LoggerInterface $logger,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $this->httpClient = $client;
        $this->logger = $logger;

        $settings = $settingsRepo->readSettings();
        $this->apiKey = $settings->getLastFmApiKey();
    }

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    public function getAlbumArt(Entity\SongInterface $song): ?string
    {
        if (!$this->hasApiKey()) {
            throw new \InvalidArgumentException('No last.fm API key provided.');
        }

        if ($song instanceof Entity\StationMedia && !empty($song->getAlbum())) {
            return $this->getAlbumArtFromMedia($song);
        }

        $response = $this->makeRequest(
            [
                'method' => 'track.getInfo',
                'artist' => $song->getArtist(),
                'track' => $song->getTitle(),
            ]
        );

        if (isset($response['album'])) {
            return $this->getImageFromArray($response['album']['image'] ?? []);
        }

        return null;
    }

    public function getAlbumArtFromMedia(Entity\StationMedia $media): ?string
    {
        if (!$this->hasApiKey()) {
            throw new \InvalidArgumentException('No last.fm API key provided.');
        }

        $response = $this->makeRequest(
            [
                'method' => 'album.getInfo',
                'artist' => $media->getArtist(),
                'album' => $media->getAlbum(),
            ]
        );

        if (isset($response['album'])) {
            return $this->getImageFromArray($response['album']['image'] ?? []);
        }

        return null;
    }

    protected function getImageFromArray(array $images): ?string
    {
        $imagesBySize = [];
        foreach ($images as $image) {
            $size = ('' === $image['size']) ? 'default' : $image['size'];
            $imagesBySize[$size] = $image['#text'];
        }

        return $imagesBySize['large']
            ?? $imagesBySize['extralarge']
            ?? $imagesBySize['default']
            ?? null;
    }

    /**
     * @param mixed[] $query Query string parameters to supplement defaults.
     *
     * @return mixed[] The decoded JSON response.
     */
    protected function makeRequest(array $query): array
    {
        $query = array_merge(
            $query,
            [
                'api_key' => $this->apiKey,
                'format' => 'json',
            ]
        );

        $response = $this->httpClient->request(
            'GET',
            self::API_BASE_URL,
            [
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                    'Accept' => 'application/json',
                ],
                RequestOptions::QUERY => $query,
            ]
        );

        $responseBody = (string)$response->getBody();
        $responseJson = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if (!empty($responseJson['error'])) {
            if (!empty($responseJson['message'])) {
                throw new \RuntimeException(
                    'last.fm API error: ' . $responseJson['message'],
                    (int)$responseJson['error']
                );
            }

            throw $this->createExceptionFromErrorCode((int)$responseJson['error']);
        }

        return $responseJson;
    }

    protected function createExceptionFromErrorCode(int $errorCode): \RuntimeException
    {
        $errorDescriptions = [
            2 => 'Invalid service - This service does not exist',
            3 => 'Invalid Method - No method with that name in this package',
            4 => 'Authentication Failed - You do not have permissions to access the service',
            5 => 'Invalid format - This service doesn\'t exist in that format',
            6 => 'Invalid parameters - Your request is missing a required parameter',
            7 => 'Invalid resource specified',
            8 => 'Operation failed - Something else went wrong',
            9 => 'Invalid session key - Please re-authenticate',
            10 => 'Invalid API key - You must be granted a valid key by last.fm',
            11 => 'Service Offline - This service is temporarily offline. try again later.',
            13 => 'Invalid method signature supplied',
            16 => 'There was a temporary error processing your request. Please try again',
            26 => 'Suspended API key - Access for your account has been suspended, please contact Last.fm',
            29 => 'Rate limit exceeded - Your IP has made too many requests in a short period',
        ];

        return new \RuntimeException(
            'last.fm API error: ' . ($errorDescriptions[$errorCode] ?? 'Unknown Error'),
            $errorCode
        );
    }
}
