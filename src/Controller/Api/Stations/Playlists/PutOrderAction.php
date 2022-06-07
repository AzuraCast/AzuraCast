<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PutOrderAction
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

        if (
            PlaylistSources::Songs !== $record->getSourceEnum()
            || PlaylistOrders::Sequential !== $record->getOrderEnum()
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $order = $request->getParam('order');

        $this->spmRepo->setMediaOrder($record, $order);
        return $response->withJson($order);
    }
}
