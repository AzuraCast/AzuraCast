<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Requests;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\StationRequest;
use App\Entity\User;
use App\Enums\StationFeatures;
use App\Exception\Http\CannotCompleteActionException;
use App\Exception\Http\InvalidRequestAttribute;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Blocklist\BlocklistParser;
use App\Service\DeviceDetector;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Post(
        path: '/station/{station_id}/request/{request_id}',
        operationId: 'submitSongRequest',
        description: 'Submit a song request.',
        tags: ['Stations: Song Requests'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'request_id',
                description: 'The requestable song ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class SubmitAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly StationMediaRepository $mediaRepo,
        private readonly StationRequestRepository $requestRepo,
        private readonly DeviceDetector $deviceDetector,
        private readonly BlocklistParser $blocklistParser
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $trackId = Types::string($params['media_id']);

        // Verify that the station supports requests.
        $station = $request->getStation();
        StationFeatures::Requests->assertSupportedForStation($station);

        try {
            $user = $request->getUser();
        } catch (InvalidRequestAttribute) {
            $user = null;
        }

        $isAuthenticated = ($user instanceof User);

        $ip = $this->readSettings()->getIp($request);
        $userAgent = $request->getHeaderLine('User-Agent');

        // Forbid web crawlers from using this feature.
        $dd = $this->deviceDetector->parse($userAgent);

        if ($dd->isBot) {
            throw CannotCompleteActionException::submitRequest(
                $request,
                __('Search engine crawlers are not permitted to use this feature.')
            );
        }

        // Check frontend blocklist and apply it to requests.
        if (!$this->blocklistParser->isAllowed($station, $ip, $userAgent)) {
            throw CannotCompleteActionException::submitRequest(
                $request,
                __('You are not permitted to submit requests.')
            );
        }

        // Verify that Track ID exists with station.
        $mediaItem = $this->mediaRepo->requireByUniqueId($trackId, $station);

        if (!$mediaItem->isRequestable()) {
            throw CannotCompleteActionException::submitRequest(
                $request,
                __('This track is not requestable.')
            );
        }

        // Check if the song is already enqueued as a request.
        if ($this->requestRepo->isTrackPending($mediaItem, $station)) {
            throw CannotCompleteActionException::submitRequest(
                $request,
                __('This song was already requested and will play soon.')
            );
        }

        // Check the most recent song history.
        if ($this->requestRepo->hasPlayedRecently($mediaItem, $station)) {
            throw CannotCompleteActionException::submitRequest(
                $request,
                __('This song or artist has been played too recently. Wait a while before requesting it again.')
            );
        }

        if (!$isAuthenticated) {
            // Check for any request (on any station) within the last $threshold_seconds.
            $thresholdMins = $station->getRequestDelay() ?? 5;
            $thresholdSeconds = $thresholdMins * 60;

            // Always have a minimum threshold to avoid flooding.
            if ($thresholdSeconds < 60) {
                $thresholdSeconds = 15;
            }

            $recentRequests = (int)$this->em->createQuery(
                <<<'DQL'
                    SELECT COUNT(sr.id) FROM App\Entity\StationRequest sr
                    WHERE sr.ip = :user_ip
                    AND sr.timestamp >= :threshold
                DQL
            )->setParameter('user_ip', $ip)
                ->setParameter('threshold', time() - $thresholdSeconds)
                ->getSingleScalarResult();

            if ($recentRequests > 0) {
                throw CannotCompleteActionException::submitRequest(
                    $request,
                    __('You have submitted a request too recently! Please wait before submitting another one.')
                );
            }
        }

        // Save request locally.
        $record = new StationRequest($station, $mediaItem, $ip);
        $this->em->persist($record);
        $this->em->flush();

        return $response->withJson(
            new Status(true, __('Your request has been submitted and will be played soon.'))
        );
    }
}
