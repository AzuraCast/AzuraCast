<?php
namespace App\Form;

use App\Customization;
use App\Entity;
use App\Http\ServerRequest;
use App\Radio\PlaylistParser;
use Azura\Config;
use AzuraForms\Field\Markup;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StationPlaylistForm extends EntityForm
{
    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlist_media_repo;

    /**
     * @param EntityManager $em
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param Config $config
     * @param Customization $customization
     */
    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Config $config,
        Customization $customization
    ) {
        $form_config = $config->get('forms/playlist', [
            'customization' => $customization,
        ]);

        parent::__construct($em, $serializer, $validator, $form_config);

        $this->entityClass = Entity\StationPlaylist::class;
        $this->playlist_media_repo = $em->getRepository(Entity\StationPlaylistMedia::class);
    }

    public function process(ServerRequest $request, $record = null)
    {
        // Set the "Station Time Zone" field.
        $station = $request->getStation();
        $station_tz = $station->getTimezone();

        $now_station = Chronos::now(new DateTimeZone($station_tz))->toIso8601String();

        $tz_string = __('This station\'s time zone is currently %s.', '<b>' . $station_tz . '</b>')
            . '<br>'
            . __('The current time in the station\'s time zone is %s.',
                '<b><time data-content="' . $now_station . '"></time></b>');

        /** @var Markup $tz_field */
        $tz_field = $this->fields['station_time_zone'];
        $tz_field->setAttribute('markup', $tz_string);

        // Resume regular record processing.
        $record = parent::process($request, $record);

        if ($record instanceof Entity\StationPlaylist) {
            $files = $request->getUploadedFiles();

            /** @var UploadedFileInterface $import_file */
            $import_file = $files['import'];
            if (UPLOAD_ERR_OK === $import_file->getError()) {
                $matches = $this->_importPlaylist($record, $import_file);

                if (is_int($matches)) {
                    $request->getSession()->flash('<b>' . __('Existing playlist imported.') . '</b><br>' . __('%d song(s) were imported into the playlist.',
                            $matches), 'blue');
                }
            }

            $record->setQueue(null);
            $this->em->persist($record);
            $this->em->flush($record);
        }

        return $record;
    }

    /**
     * @param Entity\StationPlaylist $playlist
     * @param UploadedFileInterface $playlist_file
     * @return bool|int
     */
    protected function _importPlaylist(Entity\StationPlaylist $playlist, UploadedFileInterface $playlist_file)
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

        $media_info_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT sm.id, sm.path 
            FROM App\Entity\StationMedia sm 
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station_id)
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
            for ($i = 1; $i <= count($path_parts); $i++) {
                $path_attempt = implode('/', array_slice($path_parts, 0 - $i));
                $path_hash = md5($path_attempt);

                if (isset($media_lookup[$path_hash])) {
                    $matches[] = $media_lookup[$path_hash];
                }
            }
        }

        // Assign all matched media to the playlist.
        if (!empty($matches)) {
            $matched_media = $this->em->createQuery(/** @lang DQL */ 'SELECT sm 
                FROM App\Entity\StationMedia sm
                WHERE sm.station_id = :station_id AND sm.id IN (:matched_ids)')
                ->setParameter('station_id', $station_id)
                ->setParameter('matched_ids', $matches)
                ->execute();

            $weight = $this->playlist_media_repo->getHighestSongWeight($playlist);

            foreach ($matched_media as $media) {
                $weight++;

                /** @var Entity\StationMedia $media */
                $this->playlist_media_repo->addMediaToPlaylist($media, $playlist, $weight);
            }

            $this->em->flush();
        }

        return count($matches);
    }
}
