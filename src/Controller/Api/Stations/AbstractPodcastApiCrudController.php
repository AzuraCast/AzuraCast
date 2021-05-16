<?php

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractPodcastApiCrudController extends AbstractApiCrudController
{
    /**
     * @return mixed[]
     */
    protected function getParsedBody(ServerRequest $request): array
    {
        $data = $request->getParsedBody();

        $files = $request->getUploadedFiles();
        if (!empty($files['artwork_file'])) {
            $data['artwork_file'] = $files['artwork_file'];
        }

        return $data;
    }

    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $record = $this->createRecord($this->getParsedBody($request), $request->getStation());

        return $response->withJson($this->viewRecord($record, $request));
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     */
    protected function createRecord(array $data, Entity\Station $station): object
    {
        return $this->editRecord(
            $data,
            null,
            [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    $this->entityClass => [
                        'storageLocation' => $station->getPodcastsStorageLocation(),
                    ],
                ],
            ]
        );
    }
}
