<?php

namespace App\Service;

use App\Exception\RateLimitExceededException;
use App\LockFactory;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;

class MusicBrainz
{
    public const API_BASE_URL = 'https://musicbrainz.org/ws/2/';

    public const COVER_ART_ARCHIVE_BASE_URL = 'https://coverartarchive.org/';

    protected Client $httpClient;

    protected LockFactory $lockFactory;

    public function __construct(Client $httpClient, LockFactory $lockFactory)
    {
        $this->httpClient = $httpClient;
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param string|UriInterface $uri
     * @param mixed[] $query Query string parameters to supplement defaults.
     *
     * @return mixed[] The decoded JSON response.
     */
    public function makeRequest(
        $uri,
        array $query = []
    ): array {
        $rateLimitLock = $this->lockFactory->createLock(
            'api_musicbrainz',
            1,
            false,
            500,
            10
        );

        try {
            $rateLimitLock->acquire(true);
        } catch (LockConflictedException $e) {
            throw new RateLimitExceededException('Could not acquire rate limiting lock.');
        }

        $query = array_merge(
            $query,
            [
                'fmt' => 'json',
            ]
        );

        $uri = UriResolver::resolve(
            Utils::uriFor(self::API_BASE_URL),
            Utils::uriFor($uri)
        );

        $response = $this->httpClient->request(
            'GET',
            $uri,
            [
                RequestOptions::TIMEOUT => 7,
                RequestOptions::HTTP_ERRORS => true,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                    'Accept' => 'application/json',
                ],
                RequestOptions::QUERY => $query,
            ]
        );

        $responseBody = (string)$response->getBody();
        return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getCoverArt(
        string $recordType,
        string $mbid
    ): ?string {
        $uri = '/' . $recordType . '/' . $mbid;

        $uri = UriResolver::resolve(
            Utils::uriFor(self::COVER_ART_ARCHIVE_BASE_URL),
            Utils::uriFor($uri)
        );

        $response = $this->httpClient->request(
            'GET',
            $uri,
            [
                RequestOptions::ALLOW_REDIRECTS => true,
                RequestOptions::HEADERS => [
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                    'Accept' => 'application/json',
                ],
            ]
        );

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $responseBody = (string)$response->getBody();
        if (empty($responseBody)) {
            return null;
        }

        try {
            $jsonBody = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }

        if (empty($jsonBody['images'])) {
            return null; // API returned no "images".
        }

        foreach ($jsonBody['images'] as $image) {
            if (!$image['front']) {
                continue;
            }

            $imageUrl = $image['thumbnails'][1200]
                ?? $image['thumbnails']['large']
                ?? $image['image']
                ?? null;

            if (!empty($imageUrl)) {
                return $imageUrl;
            }
        }

        return null;
    }
}
