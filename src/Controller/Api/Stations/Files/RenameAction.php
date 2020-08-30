<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RenameAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem,
        EntityManagerInterface $em,
        Entity\Repository\StationMediaRepository $mediaRepo
    ): ResponseInterface {
        $originalPath = $request->getAttribute('file');

        if (empty($originalPath)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('File not specified.')));
        }

        $newPath = $request->getParam('newPath');
        if (empty($newPath)) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('New path not specified.')));
        }

        // No-op if paths match
        if ($originalPath === $newPath) {
            return $response->withJson(new Entity\Api\Status());
        }

        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        $originalPathFull = $request->getAttribute('file_path');
        $newPathFull = Filesystem::PREFIX_MEDIA . '://' . $newPath;

        // MountManager::rename's second argument is NOT the full URI >:(
        $fs->rename($originalPathFull, $newPath);

        $pathMeta = $fs->getMetadata($newPathFull);

        if ('dir' === $pathMeta['type']) {
            // Update the paths of all media contained within the directory.
            $media_in_dir = $em->createQuery(/** @lang DQL */ 'SELECT sm FROM App\Entity\StationMedia sm
                        WHERE sm.station = :station AND sm.path LIKE :path')
                ->setParameter('station', $station)
                ->setParameter('path', $originalPath . '%')
                ->execute();

            foreach ($media_in_dir as $media_row) {
                /** @var Entity\StationMedia $media_row */
                $media_row->setPath(substr_replace($media_row->getPath(), $newPath, 0, strlen($originalPath)));
                $em->persist($media_row);
            }

            // Update the paths of all media contained within the directory.
            $playlistFolders = $em->createQuery(/** @lang DQL */ 'SELECT spf FROM App\Entity\StationPlaylistFolder spf
                        WHERE spf.station = :station AND spf.path LIKE :path')
                ->setParameter('station', $station)
                ->setParameter('path', $originalPath . '%')
                ->execute();

            foreach ($playlistFolders as $row) {
                /** @var Entity\StationPlaylistFolder $row */
                $row->setPath(substr_replace($row->getPath(), $newPath, 0, strlen($originalPath)));
                $em->persist($row);
            }
            
            $em->flush();
        } else {
            $record = $mediaRepo->findByPath($originalPath, $station);

            if ($record instanceof Entity\StationMedia) {
                $record->setPath($newPath);
                $em->persist($record);
                $em->flush();
            }
        }

        return $response->withJson(new Entity\Api\Status());
    }
}