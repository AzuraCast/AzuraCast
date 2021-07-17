<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\PlaylistParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

class ImportAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        int $id
    ): ResponseInterface {
        $playlist = $this->requireRecord($request->getStation(), $id);

        $files = $request->getUploadedFiles();

        if (empty($files['playlist_file'])) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, 'No "playlist_file" provided.'));
        }

        /** @var UploadedFileInterface $file */
        $file = $files['playlist_file'];

        if (UPLOAD_ERR_OK !== $file->getError()) {
            return $response->withStatus(500)
                ->withJson(Entity\Api\Error::fromFileError($file->getError()));
        }

        $playlistFile = $file->getStream()->getContents();

        $paths = PlaylistParser::getSongs($playlistFile);

        $totalPaths = count($paths);
        $foundPaths = 0;

        if (!empty($paths)) {
            $storageLocation = $request->getStation()->getMediaStorageLocation();

            // Assemble list of station media to match against.
            $media_lookup = [];

            $media_info_raw = $this->em->createQuery(
                <<<'DQL'
                    SELECT sm.id, sm.path
                    FROM App\Entity\StationMedia sm
                    WHERE sm.storage_location = :storageLocation
                DQL
            )->setParameter('storageLocation', $storageLocation)
                ->getArrayResult();

            foreach ($media_info_raw as $row) {
                $path_hash = md5($row['path']);
                $media_lookup[$path_hash] = $row['id'];
            }

            // Run all paths against the lookup list of hashes.
            $matches = [];

            foreach ($paths as $path_raw) {
                // De-Windows paths (if applicable)
                $path_raw = str_replace('\\', '/', $path_raw);

                // Work backwards from the basename to try to find matches.
                $path_parts = explode('/', $path_raw);
                for ($i = 1, $iMax = count($path_parts); $i <= $iMax; $i++) {
                    $path_attempt = implode('/', array_slice($path_parts, 0 - $i));
                    $path_hash = md5($path_attempt);

                    if (isset($media_lookup[$path_hash])) {
                        $matches[] = $media_lookup[$path_hash];
                    }
                }
            }

            // Assign all matched media to the playlist.
            if (!empty($matches)) {
                $matchedMediaRaw = $this->em->createQuery(
                    <<<'DQL'
                        SELECT sm
                        FROM App\Entity\StationMedia sm
                        WHERE sm.storage_location = :storageLocation AND sm.id IN (:matched_ids)
                    DQL
                )->setParameter('storageLocation', $storageLocation)
                    ->setParameter('matched_ids', $matches)
                    ->execute();

                /** @var Entity\StationMedia[] $mediaById */
                $mediaById = [];
                foreach ($matchedMediaRaw as $row) {
                    /** @var Entity\StationMedia $row */
                    $mediaById[$row->getId()] = $row;
                }

                $weight = $playlistMediaRepo->getHighestSongWeight($playlist);

                // Split this process to preserve the order of the imported items.
                foreach ($matches as $mediaId) {
                    $weight++;

                    $media = $mediaById[$mediaId];
                    $playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);

                    $foundPaths++;
                }
            }

            $this->em->flush();
        }

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __(
                    'Playlist successfully imported; %d of %d files were successfully matched.',
                    $foundPaths,
                    $totalPaths
                )
            )
        );
    }
}
