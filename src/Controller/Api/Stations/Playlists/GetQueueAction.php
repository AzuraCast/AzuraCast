<?php

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Psr\Http\Message\ResponseInterface;

class GetQueueAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        if (Entity\StationPlaylist::SOURCE_SONGS !== $record->getSource()) {
            throw new \InvalidArgumentException('This playlist does not have songs as its primary source.');
        }

        if (Entity\StationPlaylist::ORDER_RANDOM === $record->getOrder()) {
            throw new \InvalidArgumentException('This playlist is always shuffled and has no visible queue.');
        }

        $queue = $spmRepo->getQueue($record);
        $paginator = Paginator::fromArray($queue, $request);

        return $paginator->write($response);
    }
}
