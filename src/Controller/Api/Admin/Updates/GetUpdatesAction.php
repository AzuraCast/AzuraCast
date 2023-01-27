<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Updates;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;

final class GetUpdatesAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo,
        private readonly AzuraCastCentral $azuracastCentral
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $settings = $this->settingsRepo->readSettings();

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $settings->setUpdateResults($updates);
                $settings->updateUpdateLastRun();
                $this->settingsRepo->writeSettings($settings);

                return $response->withJson($updates);
            }

            throw new \RuntimeException('Error parsing update data response from AzuraCast central.');
        } catch (TransferException $e) {
            throw new \RuntimeException(
                sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage())
            );
        }
    }
}
