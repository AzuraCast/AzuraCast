<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class GetQueueAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        if (Entity\StationPlaylist::SOURCE_SONGS !== $record->getSource()) {
            throw new InvalidArgumentException('This playlist does not have songs as its primary source.');
        }

        if (Entity\StationPlaylist::ORDER_RANDOM === $record->getOrder()) {
            throw new InvalidArgumentException('This playlist is always shuffled and has no visible queue.');
        }

        $queue = $spmRepo->getQueue($record);
        return Paginator::fromArray($queue, $request)->write($response);
    }
}
