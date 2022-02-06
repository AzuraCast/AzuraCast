<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\PlaylistParser;
use App\Utilities\File;
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

        $importResults = [];

        if (!empty($paths)) {
            $storageLocation = $request->getStation()->getMediaStorageLocation();

            // Assemble list of station media to match against.
            $mediaLookup = [];
            $basenameLookup = [];

            $media_info_raw = $this->em->createQuery(
                <<<'DQL'
                    SELECT sm.id, sm.path
                    FROM App\Entity\StationMedia sm
                    WHERE sm.storage_location = :storageLocation
                DQL
            )->setParameter('storageLocation', $storageLocation)
                ->getArrayResult();

            foreach ($media_info_raw as $row) {
                $pathParts = explode('/', $row['path']);
                $basename = File::sanitizeFileName(array_pop($pathParts));

                $path = (!empty($pathParts))
                    ? implode('/', $pathParts) . '/' . $basename
                    : $basename;

                $mediaLookup[$path] = $row['id'];
                $basenameLookup[$basename] = $row['id'];
            }

            // Run all paths against the lookup list of hashes.
            $matches = [];

            $matchFunction = static function ($path_raw) use ($mediaLookup, $basenameLookup) {
                // De-Windows paths (if applicable)
                $path_raw = str_replace('\\', '/', $path_raw);

                // Work backwards from the basename to try to find matches.
                $pathParts = explode('/', $path_raw);
                $basename = File::sanitizeFileName(array_pop($pathParts));
                $pathParts[] = $basename;

                // Attempt full path matching if possible
                if (count($pathParts) >= 2) {
                    for ($i = 2, $iMax = count($pathParts); $i <= $iMax; $i++) {
                        $path = implode('/', array_slice($pathParts, 0 - $i));
                        if (isset($mediaLookup[$path])) {
                            return [$path, $mediaLookup[$path]];
                        }
                    }
                }

                // Attempt basename-only matching
                if (isset($basenameLookup[$basename])) {
                    return [$basename, $basenameLookup[$basename]];
                }

                return [null, null];
            };

            foreach ($paths as $path_raw) {
                [$matchedPath, $match] = $matchFunction($path_raw);

                $importResults[] = [
                    'path' => $path_raw,
                    'match' => $matchedPath,
                ];

                if (null !== $match) {
                    $matches[] = $match;
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
            new Entity\Api\StationPlaylistImportResult(
                true,
                __(
                    'Playlist successfully imported; %d of %d files were successfully matched.',
                    $foundPaths,
                    $totalPaths
                ),
                null,
                $importResults
            )
        );
    }
}
