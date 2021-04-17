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
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        $spmRepo->resetQueue($record);

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Playlist queue cleared.')
            )
        );
    }
}
