<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

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

        $output = $backend->updateMetadata($station, $request->getParams());

        return $response->withJson(
            new Status(true, 'Metadata updated successfully: ' . implode(', ', $output))
        );
    }
}
