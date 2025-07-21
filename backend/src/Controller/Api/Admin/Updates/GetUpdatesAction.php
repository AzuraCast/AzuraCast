<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Updates;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\UpdateDetails;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\AzuraCastCentral;
use GuzzleHttp\Exception\TransferException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[OA\Get(
    path: '/admin/updates',
    operationId: 'getUpdateStatus',
    summary: 'Show information about this installation and its update status.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: UpdateDetails::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class GetUpdatesAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly AzuraCastCentral $azuracastCentral
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->readSettings();

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $settings->update_results = $updates;
                $settings->updateUpdateLastRun();
                $this->writeSettings($settings);

                return $response->withJson(UpdateDetails::fromParent($updates));
            }

            throw new RuntimeException('Error parsing update data response from AzuraCast central.');
        } catch (TransferException $e) {
            throw new RuntimeException(
                sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage())
            );
        }
    }
}
