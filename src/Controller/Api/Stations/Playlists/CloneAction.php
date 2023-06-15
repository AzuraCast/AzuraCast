<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class CloneAction extends AbstractClonableAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $data = (array)$request->getParsedBody();
        $toClone = $data['clone'] ?? [];

        $this->clone(
            $record,
            $data['name'],
            in_array('schedule', $toClone, true),
            in_array('media', $toClone, true)
        );

        $this->em->flush();

        return $response->withJson(Status::created());
    }
}
