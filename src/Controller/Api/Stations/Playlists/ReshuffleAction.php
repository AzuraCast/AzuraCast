<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ReshuffleAction
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly StationPlaylistMediaRepository $spmRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $this->spmRepo->resetQueue($record);

        return $response->withJson(
            new Status(
                true,
                __('Playlist reshuffled.')
            )
        );
    }
}
