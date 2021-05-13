<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Files;

use App\Entity;
use App\Entity\Repository\PodcastMediaRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Psr\Http\Message\ResponseInterface;

class FlowUploadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $entityManager,
        PodcastMediaRepository $podcastMediaRepository
    ): ResponseInterface {
        $station = $request->getStation();

        $podcastsStorageLocation = $station->getPodcastsStorageLocation();

        if ($podcastsStorageLocation->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        try {
            $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
            if ($flowResponse instanceof ResponseInterface) {
                return $flowResponse;
            }

            if (is_array($flowResponse)) {
                $params = $request->getParams();

                $directory = ltrim($params['directory'] ?? '', '/');
                $sanitizedName = $flowResponse['filename'];

                $finalPath = empty($directory)
                    ? $directory . $sanitizedName
                    : $directory . '/' . $sanitizedName;

                $podcastMediaRepository->getOrCreate(
                    $station,
                    $finalPath,
                    $flowResponse['path']
                );

                $podcastsStorageLocation->addStorageUsed($flowResponse['size']);
                $entityManager->flush();

                return $response->withJson(new Entity\Api\Status());
            }
        } catch (Exception | Error $exception) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, $exception->getMessage()));
        }

        return $response->withJson(['success' => false]);
    }
}
