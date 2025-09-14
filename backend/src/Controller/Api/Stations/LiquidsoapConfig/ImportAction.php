<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/liquidsoap-config/import',
    operationId: 'importStationLiquidsoapConfig',
    summary: 'Import a previously exported Liquidsoap configuration archive and replace custom config sections.',
    tags: [OpenApi::TAG_STATIONS_BROADCASTING],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        // TODO: API Response Body
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ImportAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        // Handle Flow upload.
        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $config = $flowResponse->readAndDeleteUploadedFile();

        $config = File::convertToLf($config);

        // Look for custom config blocks.
        preg_match_all(
            "/# startcustomconfig\(([a-zA-Z_-]+)\)\n([\s\S]*?)\n# endcustomconfig\(\\1\)/",
            $config,
            $matches,
            PREG_SET_ORDER
        );

        if (!empty($matches)) {
            $backendConfig = $station->backend_config;

            foreach ($matches as $match) {
                [, $section, $contents] = $match;

                $backendConfig->setCustomConfigurationSection(
                    $section,
                    $contents
                );
            }

            $station->backend_config = $backendConfig;
            $this->em->persist($station);
            $this->em->flush();
        }

        return $response->withJson(Status::updated());
    }
}
