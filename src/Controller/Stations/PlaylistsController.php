<?php
namespace App\Controller\Stations;

use App\Csrf;
use App\Flash;
use App\Mvc\View;
use App\Url;
use App\Radio\Backend\BackendAbstract;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use App\Entity;
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

    /** @var Entity\Repository\BaseRepository */
    protected $playlist_repo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlist_media_repo;

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

        $this->playlist_repo = $this->em->getRepository(Entity\StationPlaylist::class);
        $this->playlist_media_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        if (!$backend->supportsMedia()) {
            throw new \App\Exception(__('This feature is not currently supported on this station.'));
        }

        /** @var Entity\StationPlaylist[] $all_playlists */
        $all_playlists = $station->getPlaylists();

        $total_weights = 0;
        foreach ($all_playlists as $playlist) {
            if ($playlist->getIsEnabled() && $playlist->getType() === 'default') {
                $total_weights += $playlist->getWeight();
            }
        }

        $playlists = [];

        foreach ($all_playlists as $playlist) {
            $playlist_row = $this->playlist_repo->toArray($playlist);

            if ($playlist->getIsEnabled() && $playlist->getType() === 'default') {
                $playlist_row['probability'] = round(($playlist->getWeight() / $total_weights) * 100, 1) . '%';
            }

            $playlist_row['num_songs'] = $playlist->getMediaItems()->count();
            $playlists[$playlist->getId()] = $playlist_row;
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/playlists/index', [
            'playlists' => $playlists,
            'csrf' => $this->csrf->generate($this->csrf_namespace),
            'schedule_now' => Chronos::now()->toIso8601String(),
            'schedule_url' => $this->url->named('stations:playlists:schedule', ['station' => $station_id]),
        ]);
    }

    /**
     * Controller used to respond to AJAX requests from the playlist "Schedule View".
     *
     * @param Request $request
     * @param Response $response
     * @param $station_id
     * @return Response
     */
    public function scheduleAction(Request $request, Response $response, $station_id): Response
    {
        $utc = new \DateTimeZone('UTC');
        $user_tz = new \DateTimeZone(date_default_timezone_get());

        $start_date_str = substr($request->getParam('start'), 0, 10);
        $start_date = Chronos::createFromFormat('Y-m-d', $start_date_str, $utc)
            ->subDay();

        $end_date_str = substr($request->getParam('end'), 0, 10);
        $end_date = Chronos::createFromFormat('Y-m-d', $end_date_str, $utc);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var Entity\StationPlaylist[] $all_playlists */
        $playlists = $station->getPlaylists()->filter(function($record) {
            /** @var Entity\StationPlaylist $record */
            return ($record->getType() === 'scheduled');
        });

        $events = [];
        $i = $start_date;

        while($i <= $end_date) {

            $day_of_week = $i->format('N');

            foreach ($playlists as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if (!empty($playlist->getScheduleDays()) && !in_array($day_of_week, $playlist->getScheduleDays())) {
                    continue;
                }

                $playlist_start = Entity\StationPlaylist::getDateTime($playlist->getScheduleStartTime(), $i);
                $playlist_end = Entity\StationPlaylist::getDateTime($playlist->getScheduleEndTime(), $i);

                // Handle overnight playlists
                if ($playlist_end < $playlist_start) {
                    $playlist_end = $playlist_end->addDay();
                }

                $playlist_start = $playlist_start->setTimezone($user_tz);
                $playlist_end = $playlist_end->setTimezone($user_tz);

                $events[] = [
                    'id' => $playlist->getId(),
                    'title' => $playlist->getName(),
                    'allDay' => $playlist_start->eq($playlist_end),
                    'start' => $playlist_start->toIso8601String(),
                    'end' => $playlist_end->toIso8601String(),
                    'url' => $this->url->named('stations:playlists:edit', ['station' => $station_id, 'id' => $playlist->getId()]),
                ];
            }

            $i = $i->addDay();
        }

        return $response->withJson($events);
    }

    public function reorderAction(Request $request, Response $response, $station_id, $id): Response
    {
        $record = $this->playlist_repo->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationPlaylist)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Playlist')));
        }

        if ($record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
            throw new \App\Exception(__('This playlist is not a sequential playlist.'));
        }

        if ($request->isPost()) {
            try {
                $this->csrf->verify($request->getParam('csrf'), $this->csrf_namespace);
            } catch(\App\Exception\CsrfValidation $e) {
                return $response->withStatus(403)
                    ->withJson(['error' => ['code' => 403, 'msg' => 'CSRF Failure: '.$e->getMessage()]]);
            }

            $order_raw = $request->getParam('order');
            $order = json_decode($order_raw, true);

            $mapping = [];
            foreach($order as $weight => $row) {
                $mapping[$row['id']] = $weight+1;
            }

            $this->playlist_media_repo->setMediaOrder($record, $mapping);

            return $response->withJson($mapping);
        }

        $media_items = $this->em->createQuery('SELECT spm, sm FROM Entity\StationPlaylistMedia spm
            JOIN spm.media sm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $id)
            ->getArrayResult();

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/playlists/reorder', [
            'playlist' => $record,
            'csrf' => $this->csrf->generate($this->csrf_namespace),
            'media_items' => $media_items,
        ]);
    }

    public function exportAction(Request $request, Response $response, $station_id, $id, $format = 'pls'): Response
    {
        $record = $this->playlist_repo->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if (!($record instanceof Entity\StationPlaylist)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Playlist')));
        }

        $formats = [
            'pls' => 'audio/x-scpls',
            'm3u' => 'application/x-mpegURL',
        ];

        if (!isset($formats[$format])) {
            throw new \App\Exception\NotFound(__('%s not found.', __('Format')));
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

        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->playlist_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id
            ]);

            if ($record instanceof Entity\StationPlaylist) {
                $data = $this->playlist_repo->toArray($record);
                $form->populate($data);
            }
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationPlaylist)) {
                $record = new Entity\StationPlaylist($station);
            }

            $this->playlist_repo->fromArray($record, $data);
            $this->em->persist($record);
            $this->em->flush();

            // Handle importing a playlist file, if necessary.
            $files = $request->getUploadedFiles();

            /** @var UploadedFile $import_file */
            $import_file = $files['import'];
            if ($import_file->getError() == UPLOAD_ERR_OK) {
                $this->_importPlaylist($record, $import_file, $station_id);
            }

            // If using Manual AutoDJ mode, check for changes and flag as needing-restart.
            if ($station->useManualAutoDJ()) {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets();
                if ($uow->isEntityScheduled($record)) {
                    $station->setNeedsRestart(true);
                    $this->em->persist($station);
                }
            }

            $this->em->flush();
            $this->em->refresh($station);

            $this->flash->alert('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Playlist')) . '</b>', 'green');

            return $response->redirectToRoute('stations:playlists:index', ['station' => $station_id]);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/playlists/edit', [
            'form' => $form,
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Playlist'))
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
            $matched_media = $this->em->createQuery('SELECT sm FROM Entity\StationMedia sm
                WHERE sm.station_id = :station_id AND sm.id IN (:matched_ids)')
                ->setParameter('station_id', $station_id)
                ->setParameter('matched_ids', $matches)
                ->execute();

            foreach($matched_media as $media) {
                /** @var Entity\StationMedia $media */
                $this->playlist_media_repo->addMediaToPlaylist($media, $playlist);
            }

            $this->em->persist($playlist);
        }

        $this->flash->alert('<b>' . __('Existing playlist imported.') . '</b><br>' . __('%d song(s) were imported into the playlist.', count($matches)), 'blue');
        return true;
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->playlist_repo->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationPlaylist) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($station);

        $this->flash->alert('<b>' . __('%s deleted.', __('Playlist')) . '</b>', 'green');

        return $response->redirectToRoute('stations:playlists:index', ['station' => $station_id]);
    }
}
