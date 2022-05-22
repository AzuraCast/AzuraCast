<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class ReshuffleAction extends AbstractPlaylistsAction
{
    public function __construct(
        EntityManagerInterface $em,
        private readonly Entity\Repository\StationPlaylistMediaRepository $spmRepo,
    ) {
        parent::__construct($em);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        $this->spmRepo->resetQueue($record);

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Playlist reshuffled.')
            )
        );
    }
}
