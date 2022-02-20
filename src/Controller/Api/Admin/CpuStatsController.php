<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\CpuStats;
use App\Service\CpuStats\CpuData;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/cpu/stats',
        operationId: 'getCpuStats',
        description: 'Return a list of all CPU usage stats.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: CPU stats'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success' // TODO: Response Body
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
class CpuStatsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $firstMeasurement = CpuStats::getCurrentLoad();

        sleep(1);

        $secondMeasurement = CpuStats::getCurrentLoad();

        $statsPerCore = [];

        foreach ($secondMeasurement as $index => $currentCoreData) {
            $previousCpuData = $firstMeasurement[$index];
            $deltaCpuData = CpuStats::calculateDelta($currentCoreData, $previousCpuData);

            $statsPerCore[] = [
                'name' => $deltaCpuData->name,
                'usage' => CpuStats::getUsage($deltaCpuData),
                'idle' => CpuStats::getIdle($deltaCpuData),
                'io_wait' => CpuStats::getIoWait($deltaCpuData),
                'steal' => CpuStats::getSteal($deltaCpuData),
            ];
        }

        return $response->withJson($statsPerCore);
    }
}
