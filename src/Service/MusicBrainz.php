<?php

namespace App\Service;

use App\Version;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

class MusicBrainz
{
    public const API_BASE_URL = 'https://musicbrainz.org/ws/2/';

    protected Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
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
}
