<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ReshuffleAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        $spmRepo->resetQueue($record);

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Playlist reshuffled.')
            )
        );
    }
}
