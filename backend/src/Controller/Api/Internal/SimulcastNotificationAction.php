<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\SimulcastingStatus;
use App\Entity\Simulcasting;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Centrifugo;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class SimulcastNotificationAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        private readonly Centrifugo $centrifugo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $data = (array)$request->getParsedBody();

        // Check if this is a reset_all action
        $action = Types::stringOrNull($data['action'] ?? null);
        if ($action === 'reset_all') {
            $this->logger->info('Resetting all simulcast instances for station', [
                'station_id' => $station->id,
            ]);

            // Use the repository to reset all instances
            $simulcastingRepo = $this->em->getRepository(Simulcasting::class);
            $simulcastingRepo->stopAllForStation($station);

            return $response->withJson(['success' => true, 'message' => 'All simulcast instances reset']);
        }

        // Extract the instance_id and event from the payload
        $instanceId = Types::intOrNull($data['instance_id'] ?? null);
        $event = Types::stringOrNull($data['event'] ?? null);
        $reason = Types::stringOrNull($data['reason'] ?? null);

        if (null === $instanceId || null === $event) {
            $this->logger->warning('Invalid simulcast notification payload', [
                'station_id' => $station->id,
                'data' => $data,
            ]);

            return $response->withStatus(400)->withJson([
                'success' => false,
                'error' => 'Missing required fields: instance_id and event',
            ]);
        }

        // Find the simulcast instance
        $simulcasting = $this->em->find(Simulcasting::class, $instanceId);
        if (!$simulcasting || $simulcasting->getStation()->id !== $station->id) {
            $this->logger->warning('Simulcast instance not found or station mismatch', [
                'station_id' => $station->id,
                'instance_id' => $instanceId,
            ]);

            return $response->withStatus(404)->withJson([
                'success' => false,
                'error' => 'Simulcast instance not found',
            ]);
        }

        // Update status based on event
        $newStatus = $this->mapEventToStatus($event);
        if ($newStatus) {
            $simulcasting->setStatus($newStatus);
            $simulcasting->setErrorMessage(null);

            // Set error message for error events
            if ($event === 'errored' && $reason) {
                $simulcasting->setErrorMessage($reason);
            }

            $this->em->persist($simulcasting);
            $this->em->flush();

            // Publish SSE update
            if ($this->centrifugo->isSupported()) {
                $this->centrifugo->publishSimulcastStatus($station, $simulcasting);
            }

            $this->logger->info('Simulcast status updated', [
                'station_id' => $station->id,
                'simulcasting_id' => $instanceId,
                'event' => $event,
                'new_status' => $newStatus->value,
                'reason' => $reason,
            ]);
        }

        return $response->withJson([
            'success' => true,
            'message' => 'Simulcast status updated',
        ]);
    }

    private function mapEventToStatus(string $event): ?SimulcastingStatus
    {
        return match ($event) {
            'started' => SimulcastingStatus::Running,
            'stopped' => SimulcastingStatus::Stopped,
            'errored' => SimulcastingStatus::Error,
            default => null,
        };
    }
}
