<?php
namespace Controller\Stations;

use App\Csrf;
use App\Flash;
use App\Mvc\View;
use App\Url;
use AzuraCast\Radio\Backend\BackendAbstract;
use Doctrine\ORM\EntityManager;
use Entity;
use Slim\Http\UploadedFile;
use App\Http\Request;
use App\Http\Response;

class PlaylistsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Url */
    protected $url;

    /** @var Flash */
    protected $flash;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'stations_playlists';

    /** @var array */
    protected $form_config;

    /**
     * PlaylistsController constructor.
     * @param EntityManager $em
     * @param Url $url
     * @param Flash $flash
     * @param array $form_config
     */
    public function __construct(EntityManager $em, Url $url, Flash $flash, Csrf $csrf, array $form_config)
    {
        $this->em = $em;
        $this->url = $url;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        if (!$backend->supportsMedia()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        /** @var Entity\StationPlaylist[] $all_playlists */
        $all_playlists = $station->getPlaylists();

        /** @var Entity\Repository\BaseRepository $playlist_repo */
        $playlist_repo = $this->em->getRepository(Entity\StationPlaylist::class);

        $total_weights = 0;
        foreach ($all_playlists as $playlist) {
            if ($playlist->getIsEnabled() && $playlist->getType() === 'default') {
                $total_weights += $playlist->getWeight();
            }
        }

        $playlists = [];

        $schedule_days = [
            1 => _('Monday'),
            2 => _('Tuesday'),
            3 => _('Wednesday'),
            4 => _('Thursday'),
            5 => _('Friday'),
            6 => _('Saturday'),
            7 => _('Sunday'),
        ];
        $schedule = [];

        foreach ($all_playlists as $playlist) {
            $playlist_row = $playlist_repo->toArray($playlist);

            if ($playlist->getIsEnabled() && $playlist->getType() === 'default') {
                $playlist_row['probability'] = round(($playlist->getWeight() / $total_weights) * 100, 1) . '%';
            }

            $playlist_row['num_songs'] = $playlist->getMedia()->count();

            // Append to schedule display if the playlist is scheduled.
            if ($playlist->getType() === 'scheduled') {
                foreach($schedule_days as $day_key => $day_name) {
                    if (empty($playlist->getScheduleDays()) || in_array($day_key, $playlist->getScheduleDays())) {

                        $schedule_options = [
                            'id' => $playlist->getId(),
                            'url' => $this->url->named('stations:playlists:edit', ['station' => $station_id, 'id' => $playlist->getId()])
                        ];

                        $start = Entity\StationPlaylist::getTimestamp($playlist->getScheduleStartTime());
                        $end = Entity\StationPlaylist::getTimestamp($playlist->getScheduleEndTime());

                        if (date('Gi', $end) < date('Gi', $start)) {
                            // Overnight playlist - Create two "events"
                            $schedule[] = [
                                'name' => $playlist->getName(),
                                'day' => $day_name,
                                'start_hour' => 0,
                                'start_min' => 0,
                                'end_hour' => (int)date('G', $end),
                                'end_min' => (int)date('i', $end),
                                'options' => $schedule_options,
                            ];
                            $schedule[] = [
                                'name' => $playlist->getName(),
                                'day' => $day_name,
                                'start_hour' => (int)date('G', $start),
                                'start_min' => (int)date('i', $start),
                                'end_hour' => 23,
                                'end_min' => 59,
                                'options' => $schedule_options,
                            ];
                        } else {
                            // Normal playlist
                            $schedule[] = [
                                'name' => $playlist->getName(),
                                'day' => $day_name,
                                'start_hour' => (int)date('G', $start),
                                'start_min' => (int)date('i', $start),
                                'end_hour' => (int)date('G', $end),
                                'end_min' => (int)date('i', $end),
                                'options' => $schedule_options,
                            ];
                        }
                    }
                }
            }

            $playlists[$playlist->getId()] = $playlist_row;
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/playlists/index', [
            'playlists' => $playlists,
            'schedule' => $schedule,
            'schedule_days' => $schedule_days,
            'csrf' => $this->csrf->generate($this->csrf_namespace),
        ]);
    }

    public function exportAction(Request $request, Response $response, $station_id, $id, $format = 'pls'): Response
    {
        $record = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationPlaylist)) {
            throw new \Exception('Playlist not found!');
        }

        $formats = [
            'pls' => 'audio/x-scpls',
            'm3u' => 'application/x-mpegURL',
        ];

        if (!isset($formats[$format])) {
            throw new \Exception('Format not found!');
        }

        $file_name = 'playlist_' . $record->getShortName().'.'.$format;

        return $response
            ->withHeader('Content-Type', $formats[$format])
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name)
            ->write($record->export($format));
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var Entity\Repository\BaseRepository $playlist_repo */
        $playlist_repo = $this->em->getRepository(Entity\StationPlaylist::class);

        $form = new \App\Form($this->form_config);

        if (!empty($id)) {
            $record = $playlist_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id
            ]);

            if ($record instanceof Entity\StationPlaylist) {
                $data = $playlist_repo->toArray($record);
                $form->setDefaults($data);
            }
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationPlaylist)) {
                $record = new Entity\StationPlaylist($station);
            }

            $playlist_repo->fromArray($record, $data);
            $this->em->persist($record);

            // Handle importing a playlist file, if necessary.
            $files = $request->getUploadedFiles();

            /** @var UploadedFile $import_file */
            $import_file = $files['import'];
            if ($import_file->getError() == UPLOAD_ERR_OK) {
                $this->_importPlaylist($record, $import_file, $station_id);
            }

            $this->em->flush();
            $this->em->refresh($station);

            $this->flash->alert('<b>' . _('Record updated.') . '</b>', 'green');

            return $response->redirectToRoute('stations:playlists:index', ['station' => $station_id]);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/playlists/edit', [
            'form' => $form,
            'title' => ($id) ? _('Edit Record') : _('Add Record')
        ]);
    }

    protected function _importPlaylist(Entity\StationPlaylist $playlist, UploadedFile $playlist_file, $station_id)
    {
        $playlist_raw = (string)$playlist_file->getStream();
        if (empty($playlist_raw)) {
            return false;
        }

        // Process as full PLS if the header is present.
        if (substr($playlist_raw, 0, 10) === '[playlist]') {

            $parsed_playlist = (array)parse_ini_string($playlist_raw, true, INI_SCANNER_RAW);

            $paths = [];
            foreach($parsed_playlist['playlist'] as $playlist_key => $playlist_line) {
                if (substr(strtolower($playlist_key), 0, 4) === 'file') {
                    $paths[] = $playlist_line;
                }
            }

        } else {

            // Process as a simple list of files or M3U-style playlist.
            $lines = explode("\n", $playlist_raw);
            $paths = array_filter(array_map('trim', $lines), function($line) {
                return !empty($line) && $line[0] !== '#';
            });

        }

        if (empty($paths)) {
            return false;
        }

        // Assemble list of station media to match against.
        $media_lookup = [];

        $media_info_raw = $this->em->createQuery('SELECT sm.id, sm.path FROM Entity\StationMedia sm WHERE sm.station_id = :station_id')
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
            $matched_media = $this->em->createQuery('SELECT sm, sp FROM Entity\StationMedia sm
                LEFT JOIN sm.playlists sp
                WHERE sm.station_id = :station_id AND sm.id IN (:matched_ids)')
                ->setParameter('station_id', $station_id)
                ->setParameter('matched_ids', $matches)
                ->execute();

            foreach($matched_media as $media) {
                /** @var Entity\StationMedia $media */
                if (!$media->getPlaylists()->contains($playlist)) {
                    $media->getPlaylists()->add($playlist);
                    $playlist->getMedia()->add($media);

                    $this->em->persist($media);
                }
            }

            $this->em->persist($playlist);
        }

        $this->flash->alert('<b>' . _('Existing playlist imported.') . '</b><br>' . sprintf(_('%d song(s) were imported into the playlist.'), count($matches)), 'blue');
        return true;
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationPlaylist) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($station);

        $this->flash->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $response->redirectToRoute('stations:playlists:index', ['station' => $station_id]);
    }
}
