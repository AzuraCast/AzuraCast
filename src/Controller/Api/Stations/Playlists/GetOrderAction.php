<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetOrderAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        if (
            $record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $media_items = $this->em->createQuery(
            <<<'DQL'
                SELECT spm, sm
                FROM App\Entity\StationPlaylistMedia spm
                JOIN spm.media sm
                WHERE spm.playlist_id = :playlist_id
                ORDER BY spm.weight ASC
            DQL
        )->setParameter('playlist_id', $id)
            ->getArrayResult();

        return $response->withJson($media_items);
    }
}
