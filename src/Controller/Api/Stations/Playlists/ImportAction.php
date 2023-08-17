<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\StationPlaylistImportResult;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationMedia;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\PlaylistParser;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Path;

final class ImportAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

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

        $playlist = $this->playlistRepo->requireForStation($id, $request->getStation());

        $files = $request->getUploadedFiles();

        if (empty($files['playlist_file'])) {
            return $response->withStatus(500)
                ->withJson(new Error(500, 'No "playlist_file" provided.'));
        }

        /** @var UploadedFileInterface $file */
        $file = $files['playlist_file'];

        if (UPLOAD_ERR_OK !== $file->getError()) {
            return $response->withStatus(500)
                ->withJson(Error::fromFileError($file->getError()));
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

            $mediaInfoRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT sm.id, sm.path
                    FROM App\Entity\StationMedia sm
                    WHERE sm.storage_location = :storageLocation
                DQL
            )->setParameter('storageLocation', $storageLocation)
                ->getArrayResult();

            foreach ($mediaInfoRaw as $row) {
                $pathParts = explode('/', $row['path']);

                $basename = File::sanitizeFileName(array_pop($pathParts));
                $basenameWithoutExt = Path::getFilenameWithoutExtension($basename);

                $path = (!empty($pathParts))
                    ? implode('/', $pathParts) . '/' . $basename
                    : $basename;

                $pathWithoutExt = (!empty($pathParts))
                    ? implode('/', $pathParts) . '/' . $basenameWithoutExt
                    : $basenameWithoutExt;

                $mediaLookup[$path] = $row['id'];
                $mediaLookup[$pathWithoutExt] = $row['id'];

                $basenameLookup[$basename] = $row['id'];
                $basenameLookup[$basenameWithoutExt] = $row['id'];
            }

            // Run all paths against the lookup list of hashes.
            $matches = [];

            $matchFunction = static function ($pathRaw) use ($mediaLookup, $basenameLookup) {
                // De-Windows paths (if applicable)
                $pathRaw = str_replace('\\', '/', $pathRaw);

                // Work backwards from the basename to try to find matches.
                $pathParts = explode('/', $pathRaw);

                $basename = File::sanitizeFileName(array_pop($pathParts));
                $basenameWithoutExt = Path::getFilenameWithoutExtension($basename);

                $pathPartsWithoutExt = $pathParts;

                $pathParts[] = $basename;
                $pathPartsWithoutExt[] = $basenameWithoutExt;

                // Attempt full path matching if possible
                if (count($pathParts) >= 2) {
                    for ($i = 2, $iMax = count($pathParts); $i <= $iMax; $i++) {
                        $path = implode('/', array_slice($pathParts, 0 - $i));
                        if (isset($mediaLookup[$path])) {
                            return [$path, $mediaLookup[$path]];
                        }

                        $pathWithoutExt = implode('/', array_slice($pathPartsWithoutExt, 0 - $i));
                        if (isset($mediaLookup[$pathWithoutExt])) {
                            return [$pathWithoutExt, $mediaLookup[$pathWithoutExt]];
                        }
                    }
                }

                // Attempt basename-only matching
                if (isset($basenameLookup[$basename])) {
                    return [$basename, $basenameLookup[$basename]];
                }

                if (isset($basenameLookup[$basenameWithoutExt])) {
                    return [$basenameWithoutExt, $basenameLookup[$basenameWithoutExt]];
                }

                return [null, null];
            };

            foreach ($paths as $pathRaw) {
                [$matchedPath, $match] = $matchFunction($pathRaw);

                $importResults[] = [
                    'path' => $pathRaw,
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

                /** @var StationMedia[] $mediaById */
                $mediaById = [];
                foreach ($matchedMediaRaw as $row) {
                    /** @var StationMedia $row */
                    $mediaById[$row->getId()] = $row;
                }

                $weight = $this->spmRepo->getHighestSongWeight($playlist);

                // Split this process to preserve the order of the imported items.
                foreach ($matches as $mediaId) {
                    $weight++;

                    $media = $mediaById[$mediaId];
                    $this->spmRepo->addMediaToPlaylist($media, $playlist, $weight);

                    $foundPaths++;
                }
            }

            $this->em->flush();
        }

        return $response->withJson(
            new StationPlaylistImportResult(
                true,
                sprintf(
                    __('Playlist successfully imported; %d of %d files were successfully matched.'),
                    $foundPaths,
                    $totalPaths
                ),
                null,
                $importResults
            )
        );
    }
}
