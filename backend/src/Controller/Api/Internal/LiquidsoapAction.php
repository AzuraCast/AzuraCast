<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Container\ContainerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap\Command\AbstractCommand;
use App\Radio\Enums\LiquidsoapCommands;
use App\Utilities\Types;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class LiquidsoapAction implements SingleActionInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $action = Types::string($params['action'] ?? '');

        $station = $request->getStation();
        $asAutoDj = false;
        $payload = (array)$request->getParsedBody();

        try {
            $acl = $request->getAcl();
            $authKey = $request->getHeaderLine('X-Liquidsoap-Api-Key');

            if (!$acl->isAllowed(StationPermissions::View, $station->id)) {
                if (!$station->validateAdapterApiKey($authKey)) {
                    throw new RuntimeException('Invalid API key.');
                }
                $asAutoDj = true;
            } else {
                // Even ACL-authenticated users must provide valid adapter key for AutoDJ operations
                $asAutoDj = !empty($authKey) && $station->validateAdapterApiKey($authKey);
            }

            $command = LiquidsoapCommands::tryFrom($action);
            if (null === $command || !$this->di->has($command->getClass())) {
                throw new InvalidArgumentException('Command not found.');
            }

            /** @var AbstractCommand $commandObj */
            $commandObj = $this->di->get($command->getClass());

            return $response->withJson(
                $commandObj->run($station, $asAutoDj, $payload)
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Liquidsoap command "%s" error: %s',
                    $action,
                    $e->getMessage()
                ),
                [
                    'station' => (string)$station,
                    'payload' => $payload,
                    'as-autodj' => $asAutoDj,
                ]
            );

            return $response->withStatus(400)->withJson(
                [
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ]
            );
        }
    }
}
