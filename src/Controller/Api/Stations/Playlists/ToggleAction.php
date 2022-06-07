<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ToggleAction
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $new_value = !$record->getIsEnabled();
        $record->setIsEnabled($new_value);

        $em = $this->playlistRepo->getEntityManager();
        $em->persist($record);
        $em->flush();

        $flash_message = ($new_value)
            ? __('Playlist enabled.')
            : __('Playlist disabled.');

        return $response->withJson(new Status(true, $flash_message));
    }
}
