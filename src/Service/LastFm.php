<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\SettingsAwareTrait;
use App\Exception\RateLimitExceededException;
use App\Lock\LockFactory;
use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Lock\Exception\LockConflictedException;

final class LastFm
{
    use SettingsAwareTrait;

    public const API_BASE_URL = 'https://ws.audioscrobbler.com/2.0/';

    public function __construct(
        private readonly Client $httpClient,
        private readonly LockFactory $lockFactory
    ) {
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->readSettings()->getLastFmApiKey();
    }

    public function hasApiKey(): bool
    {
        return !empty($this->getApiKey());
    }

    /**
     * @param string $apiMethod API method to call.
     * @param mixed[] $query Query string parameters to supplement defaults.
     *
     * @return mixed[] The decoded JSON response.
     */
    public function makeRequest(
        string $apiMethod,
        array $query = []
    ): array {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new InvalidArgumentException('No last.fm API key provided.');
        }

        $rateLimitLock = $this->lockFactory->createLock(
            'api_lastfm',
            1,
            false
        );

        try {
            $rateLimitLock->acquire(true);
        } catch (LockConflictedException) {
            throw new RateLimitExceededException('Could not acquire rate limiting lock.');
        }

        $query = array_merge(
            $query,
            [
                'method' => $apiMethod,
                'api_key' => $apiKey,
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
                throw new RuntimeException(
                    'last.fm API error: ' . $responseJson['message'],
                    (int)$responseJson['error']
                );
            }

            throw $this->createExceptionFromErrorCode((int)$responseJson['error']);
        }

        return $responseJson;
    }

    private function createExceptionFromErrorCode(int $errorCode): RuntimeException
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

        return new RuntimeException(
            'last.fm API error: ' . ($errorDescriptions[$errorCode] ?? 'Unknown Error'),
            $errorCode
        );
    }
}
