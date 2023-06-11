<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Updates;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

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
                $settings->setUpdateResults($updates);
                $settings->updateUpdateLastRun();
                $this->writeSettings($settings);

                return $response->withJson($updates);
            }

            throw new RuntimeException('Error parsing update data response from AzuraCast central.');
        } catch (TransferException $e) {
            throw new RuntimeException(
                sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage())
            );
        }
    }
}
