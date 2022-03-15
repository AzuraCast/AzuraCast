<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap\Command\AbstractCommand;
use App\Radio\Enums\LiquidsoapCommands;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LiquidsoapAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        ContainerInterface $di,
        LoggerInterface $logger,
        string $action
    ): ResponseInterface {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if (!$acl->isAllowed(StationPermissions::View, $station->getIdRequired())) {
            $authKey = $request->getHeaderLine('X-Liquidsoap-Api-Key');
            if (!$station->validateAdapterApiKey($authKey)) {
                $logger->error(
                    'Invalid API key supplied for internal API call.',
                    [
                        'station_id' => $station->getId(),
                        'station_name' => $station->getName(),
                    ]
                );

                $response->getBody()->write('false');
                return $response->withStatus(403);
            }
        }

        $asAutoDj = $request->hasHeader('X-Liquidsoap-Api-Key');
        $payload = (array)$request->getParsedBody();

        $command = LiquidsoapCommands::tryFrom($action);
        if (null === $command) {
            return $response;
        }

        /** @var AbstractCommand $commandObj */
        $commandObj = $di->get($command->getClass());

        $result = $commandObj->run($station, $asAutoDj, $payload);

        if ("false" === $result) {
            $response = $response->withStatus(400);
        }
        $response->getBody()->write((string)$result);
        return $response;
    }
}
