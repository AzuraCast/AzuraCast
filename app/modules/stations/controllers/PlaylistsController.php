<?php
namespace Controller\Stations;

use Entity;
use Slim\Http\UploadedFile;

class PlaylistsController extends BaseController
{
    protected function preDispatch()
    {
        if (!$this->backend->supportsMedia()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        return parent::preDispatch();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('manage station media', $this->station->getId());
    }

    public function indexAction()
    {
        /** @var Entity\StationPlaylist[] $all_playlists */
        $all_playlists = $this->station->getPlaylists();

        /** @var Entity\Repository\BaseRepository $playlist_repo */
        $playlist_repo = $this->em->getRepository(Entity\StationPlaylist::class);

        $total_weights = 0;
        foreach ($all_playlists as $playlist) {
            if ($playlist->getIsEnabled() && $playlist->getType() == 'default') {
                $total_weights += $playlist->getWeight();
            }
        }

        $playlists = [];
        foreach ($all_playlists as $playlist) {
            $playlist_row = $playlist_repo->toArray($playlist);

            if ($playlist->getIsEnabled() && $playlist->getType() == 'default') {
                $playlist_row['probability'] = round(($playlist->getWeight() / $total_weights) * 100, 1) . '%';
            }

            $playlist_row['num_songs'] = $playlist->getMedia()->count();

            $playlists[$playlist->getId()] = $playlist_row;
        }

        $this->view->playlists = $playlists;
    }

    public function exportAction()
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
            'id' => $id,
            'station_id' => $this->station->getId()
        ]);

        if (!($record instanceof Entity\StationPlaylist)) {
            throw new \Exception('Playlist not found!');
        }

        $format = $this->getParam('format', 'pls');
        $formats = [
            'pls' => 'audio/x-scpls',
            'm3u' => 'application/x-mpegURL',
        ];

        if (!isset($formats[$format])) {
            throw new \Exception('Format not found!');
        }

        $file_name = 'playlist_' . $record->getShortName().'.'.$format;

        $this->doNotRender();

        $body = $this->response->getBody();
        $body->write($record->export($format));

        return $this->response
            ->withHeader('Content-Type', $formats[$format])
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name)
            ->withBody($body);
    }

    public function editAction()
    {
        /** @var Entity\Repository\BaseRepository $playlist_repo */
        $playlist_repo = $this->em->getRepository(Entity\StationPlaylist::class);

        $form_config = $this->config->forms->playlist;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id')) {
            $record = $playlist_repo->findOneBy([
                'id' => $this->getParam('id'),
                'station_id' => $this->station->getId()
            ]);
            $form->setDefaults($playlist_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationPlaylist)) {
                $record = new Entity\StationPlaylist($this->station);
            }

            $playlist_repo->fromArray($record, $data);
            $this->em->persist($record);

            // Handle importing a playlist file, if necessary.
            $files = $this->request->getUploadedFiles();

            /** @var UploadedFile $import_file */
            $import_file = $files['import'];
            if ($import_file->getError() == UPLOAD_ERR_OK) {
                $this->_importPlaylist($record, $import_file);
            }

            $this->em->flush();

            $this->em->refresh($this->station);

            $this->alert('<b>' . _('Record updated.') . '</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        $this->view->form = $form;
        $this->view->title = ($this->hasParam('id')) ? _('Edit Record') : _('Add Record');
    }

    protected function _importPlaylist(Entity\StationPlaylist $playlist, UploadedFile $playlist_file)
    {
        $playlist_raw = (string)$playlist_file->getStream();
        if (empty($playlist_raw)) {
            return false;
        }

        // Process as full PLS if the header is present.
        if (substr($playlist_raw, 0, 10) === '[playlist]') {

            $parsed_playlist = (array)parse_ini_string($playlist_raw, true, INI_SCANNER_RAW);

            $paths = [];
            foreach($parsed_playlist['playlist'] as $playlist_key => $playlist_file) {
                if (substr(strtolower($playlist_key), 0, 4) === 'file') {
                    $paths[] = $playlist_file;
                }
            }

        } else {

            // Process as a simple list of files or M3U-style playlist.
            $lines = explode("\n", $playlist_raw);
            $paths = array_filter(array_map('trim', $lines), function($line) {
                return !empty($line) && substr($line, 0, 1) !== '#';
            });

        }

        if (empty($paths)) {
            return false;
        }

        // Assemble list of station media to match against.
        $media_lookup = [];

        $media_info_raw = $this->em->createQuery('SELECT sm.id, sm.path FROM Entity\StationMedia sm WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $this->station->getId())
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
                ->setParameter('station_id', $this->station->getId())
                ->setParameter('matched_ids', $matches)
                ->execute();

            foreach($matched_media as $media) {
                if (!$media->playlists->contains($playlist)) {
                    $media->playlists->add($playlist);
                    $playlist->getMedia()->add($media);

                    $this->em->persist($media);
                }
            }

            $this->em->persist($playlist);
        }

        $this->alert('<b>' . _('Existing playlist imported.') . '</b><br>' . sprintf(_('%d song(s) were imported into the playlist.'), count($matches)), 'blue');
        return true;
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(Entity\StationPlaylist::class)->findOneBy([
            'id' => $id,
            'station_id' => $this->station->getId()
        ]);

        if ($record instanceof Entity\StationPlaylist) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->em->refresh($this->station);

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null]);
    }
}
