<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PutApplyToAction extends AbstractClonableAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationPlaylistFolderRepository $folderRepo,
        StationPlaylistRepository $playlistRepo
    ) {
        parent::__construct($playlistRepo);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();
        $record = $this->playlistRepo->requireForStation($id, $station);

        $data = (array)$request->getParsedBody();

        $clone = $data['copyPlaylist'] ?? false;
        $directories = (array)($data['directories'] ?? []);

        foreach ($directories as $directory) {
            if ($clone) {
                $playlist = $this->clone(
                    $record,
                    $record->getName() . ' - ' . $directory
                );
            } else {
                $playlist = $record;
            }

            $this->folderRepo->addPlaylistsToFolder(
                $station,
                $directory,
                [
                    $playlist->getIdRequired() => 0,
                ]
            );
        }

        return $response->withJson(
            new Status(
                true,
                __('Playlist applied to folders.')
            )
        );
    }
}
