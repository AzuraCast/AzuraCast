<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

use const ARRAY_FILTER_USE_KEY;

final class UpdateMetadataAction implements SingleActionInterface
{
    public function __construct(
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $this->adapters->requireBackendAdapter($station);

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
            new Status(true, 'Metadata updated successfully: ' . implode(', ', $output))
        );
    }
}
