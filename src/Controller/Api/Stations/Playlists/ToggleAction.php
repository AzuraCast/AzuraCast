<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ToggleAction extends AbstractPlaylistsAction
{
    public function __invoke(ServerRequest $request, Response $response, int $id): ResponseInterface
    {
        $record = $this->requireRecord($request->getStation(), $id);

        $new_value = !$record->getIsEnabled();

        $record->setIsEnabled($new_value);
        $this->em->persist($record);
        $this->em->flush();

        $flash_message = ($new_value)
            ? __('Playlist enabled.')
            : __('Playlist disabled.');

        return $response->withJson(new Entity\Api\Status(true, $flash_message));
    }
}
