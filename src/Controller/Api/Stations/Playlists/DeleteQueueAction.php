<?php

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteQueueAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        $record->setQueue(null);
        $this->em->persist($record);

        $this->em->flush();

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Playlist queue cleared.')
            )
        );
    }
}
