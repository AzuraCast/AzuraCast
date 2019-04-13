<?php
namespace App\Form;

use App\Entity;
use App\Http\Request;
use App\Radio\PlaylistParser;
use Doctrine\ORM\EntityManager;
use Slim\Http\UploadedFile;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaylistForm extends EntityForm
{
    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlist_media_repo;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $options = [],
        ?array $defaults = null
    ) {
        parent::__construct($em, $serializer, $validator, $options, $defaults);

        $this->entityClass = Entity\StationPlaylist::class;
        $this->playlist_media_repo = $em->getRepository(Entity\StationPlaylistMedia::class);
    }

    public function process(Request $request, $record = null)
    {
        $record = parent::process($request, $record);

        if ($record instanceof Entity\StationPlaylist) {
            $files = $request->getUploadedFiles();

            /** @var UploadedFile $import_file */
            $import_file = $files['import'];
            if (UPLOAD_ERR_OK === $import_file->getError()) {
                $matches = $this->_importPlaylist($record, $import_file);

                if (is_int($matches)) {
                    $request->getSession()->flash('<b>' . __('Existing playlist imported.') . '</b><br>' . __('%d song(s) were imported into the playlist.', $matches), 'blue');
                }
            }

            $this->playlist_media_repo->reshuffleMedia($record);
            $this->playlist_media_repo->clearMediaQueue($record->getId());
        }

        return $record;
    }

    /**
     * @param Entity\StationPlaylist $playlist
     * @param UploadedFile $playlist_file
     * @return bool|int
     */
    protected function _importPlaylist(Entity\StationPlaylist $playlist, UploadedFile $playlist_file)
    {
        $station_id = $this->station->getId();

        $playlist_raw = (string)$playlist_file->getStream();
        if (empty($playlist_raw)) {
            return false;
        }

        $paths = PlaylistParser::getSongs($playlist_raw);

        if (empty($paths)) {
            return false;
        }

        // Assemble list of station media to match against.
        $media_lookup = [];

        $media_info_raw = $this->em->createQuery(/** @lang DQL */'SELECT sm.id, sm.path 
            FROM App\Entity\StationMedia sm 
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        foreach($media_info_raw as $row) {
            $path_hash = md5($row['path']);
            $media_lookup[$path_hash] = $row['id'];
        }

        // Run all paths against the lookup list of hashes.
        $matches = [];

        foreach($paths as $path_raw) {
            // De-Windows paths (if applicable)
            $path_raw = str_replace('\\', '/', $path_raw);

            // Work backwards from the basename to try to find matches.
            $path_parts = explode('/', $path_raw);
            for($i = 1; $i <= count($path_parts); $i++) {
                $path_attempt = implode('/', array_slice($path_parts, 0-$i));
                $path_hash = md5($path_attempt);

                if (isset($media_lookup[$path_hash])) {
                    $matches[] = $media_lookup[$path_hash];
                }
            }
        }

        // Assign all matched media to the playlist.
        if (!empty($matches)) {
            $matched_media = $this->em->createQuery(/** @lang DQL */'SELECT sm 
                FROM App\Entity\StationMedia sm
                WHERE sm.station_id = :station_id AND sm.id IN (:matched_ids)')
                ->setParameter('station_id', $station_id)
                ->setParameter('matched_ids', $matches)
                ->execute();

            $weight = $this->playlist_media_repo->getHighestSongWeight($playlist);

            foreach($matched_media as $media) {
                $weight++;

                /** @var Entity\StationMedia $media */
                $this->playlist_media_repo->addMediaToPlaylist($media, $playlist, $weight);
            }

            $this->em->flush();
            $this->playlist_media_repo->reshuffleMedia($playlist);
        }

        return count($matches);
    }
}
