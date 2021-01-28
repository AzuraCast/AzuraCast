<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

class RenameAction extends BatchAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $from = $request->getParam('file');
        if (empty($from)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('File not specified.')));
        }

        $to = $request->getParam('newPath');
        if (empty($to)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('New path not specified.')));
        }

        // No-op if paths match
        if ($from === $to) {
            return $response->withJson(new Entity\Api\Status());
        }

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();
        $fs = $storageLocation->getFilesystem();

        if ($fs->rename($from, $to)) {
            $pathMeta = $fs->getMetadata($to);

            if ('dir' === $pathMeta['type']) {
                // Update the paths of all media contained within the directory.
                $toRename = [
                    $this->iterateMediaInDirectory($storageLocation, $from),
                    $this->iterateUnprocessableMediaInDirectory($storageLocation, $from),
                    $this->iteratePlaylistFoldersInDirectory($station, $from),
                ];

                foreach ($toRename as $iterator) {
                    foreach ($iterator as $record) {
                        /** @var Entity\PathAwareInterface $record */
                        $record->setPath(
                            File::renameDirectoryInPath($record->getPath(), $from, $to)
                        );
                        $this->em->persist($record);
                    }
                }
            } else {
                $record = $this->mediaRepo->findByPath($from, $storageLocation);

                if ($record instanceof Entity\StationMedia) {
                    $record->setPath($to);
                    $this->em->persist($record);
                    $this->em->flush();
                } else {
                    $record = $this->unprocessableMediaRepo->findByPath($from, $storageLocation);

                    if ($record instanceof Entity\UnprocessableMedia) {
                        $record->setPath($to);
                        $this->em->persist($record);
                        $this->em->flush();
                    }
                }
            }
        }

        return $response->withJson(new Entity\Api\Status());
    }
}
