<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

use const ARRAY_FILTER_USE_KEY;

class UpdateMetadataController
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!method_exists($backend, 'updateMetadata')) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, 'This function is not supported on this station.'));
        }

        $allowedMetaFields = [
            'title',
            'artist',
            'duration',
            'song_id',
            'media_id',
            'liq_amplify',
            'liq_cross_duration',
            'liq_fade_in',
            'liq_fade_out',
            'liq_cue_in',
            'liq_cue_out',
        ];

        $metadata = array_filter(
            $request->getParams(),
            static function ($key) use ($allowedMetaFields) {
                return in_array($key, $allowedMetaFields, true);
            },
            ARRAY_FILTER_USE_KEY
        );

        $output = $backend->updateMetadata($station, $metadata);

        return $response->withJson(
            new Entity\Api\Status(true, 'Metadata updated successfully: ' . implode(', ', $output))
        );
    }
}
