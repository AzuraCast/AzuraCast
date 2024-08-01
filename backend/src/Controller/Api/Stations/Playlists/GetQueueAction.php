<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

final class GetQueueAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly StationPlaylistMediaRepository $spmRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        if (PlaylistSources::Songs !== $record->getSource()) {
            throw new InvalidArgumentException('This playlist does not have songs as its primary source.');
        }

        if (PlaylistOrders::Random === $record->getOrder()) {
            throw new InvalidArgumentException('This playlist is always shuffled and has no visible queue.');
        }

        $queue = $this->spmRepo->getQueue($record);
        return Paginator::fromArray($queue, $request)->write($response);
    }
}
