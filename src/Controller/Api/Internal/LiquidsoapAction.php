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
use App\Service\HighAvailability;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class LiquidsoapAction implements SingleActionInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    public function __construct(
        private readonly HighAvailability $highAvailability
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $action */
        $action = $params['action'];

        $station = $request->getStation();
        $asAutoDj = $request->hasHeader('X-Liquidsoap-Api-Key')
            && $this->highAvailability->isActiveServer();
        $payload = (array)$request->getParsedBody();

        try {
            $acl = $request->getAcl();
            if (!$acl->isAllowed(StationPermissions::View, $station->getIdRequired())) {
                $authKey = $request->getHeaderLine('X-Liquidsoap-Api-Key');
                if (!$station->validateAdapterApiKey($authKey)) {
                    throw new RuntimeException('Invalid API key.');
                }
            }

            $command = LiquidsoapCommands::tryFrom($action);
            if (null === $command || !$this->di->has($command->getClass())) {
                throw new InvalidArgumentException('Command not found.');
            }

            /** @var AbstractCommand $commandObj */
            $commandObj = $this->di->get($command->getClass());

            $result = $commandObj->run($station, $asAutoDj, $payload);
            $response->getBody()->write($result);
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

            return $response->withStatus(400)
                ->write('false');
        }

        return $response;
    }
}
