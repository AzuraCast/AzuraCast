<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Admin\StationsController;
use App\Entity\Api\Status;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * This controller handles the specific "Edit Profile" function on a station's profile, which has different permissions
 * and possible actions than the Admin Station Edit function.
 */
final class ProfileEditController extends StationsController
{
    public function getProfileAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        return $response->withJson(
            $this->toArray($station, $this->getContext($request))
        );
    }

    public function putProfileAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $this->editRecord((array)$request->getParsedBody(), $station, $this->getContext($request));

        return $response->withJson(Status::updated());
    }

    private function getContext(ServerRequest $request): array
    {
        $context = [
            AbstractNormalizer::GROUPS => [
                EntityGroupsInterface::GROUP_ID,
                EntityGroupsInterface::GROUP_GENERAL,
            ],
        ];

        if ($request->getAcl()->isAllowed(GlobalPermissions::Stations)) {
            $context[AbstractNormalizer::GROUPS][] = EntityGroupsInterface::GROUP_ALL;
        }

        return $context;
    }
}
