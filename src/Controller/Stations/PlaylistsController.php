<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Form\StationPlaylistForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Exception;
use Azura\Session\Flash;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;

class PlaylistsController extends AbstractStationCrudController
{
    /** @var Entity\Repository\StationPlaylistRepository */
    protected $playlist_repo;

    /** @var Entity\Repository\StationPlaylistMediaRepository */
    protected $playlist_media_repo;

    /**
     * @param StationPlaylistForm $form
     */
    public function __construct(
        StationPlaylistForm $form,
        Entity\Repository\StationPlaylistRepository $playlistRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo
    ) {
        parent::__construct($form);

        $this->csrf_namespace = 'stations_playlists';
        $this->playlist_repo = $playlistRepo;
        $this->playlist_media_repo = $spmRepo;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        if (!$backend::supportsMedia()) {
            throw new Exception(__('This feature is not currently supported on this station.'));
        }

        /** @var Entity\StationPlaylist[] $all_playlists */
        $all_playlists = $station->getPlaylists();

        $total_weights = 0;
        foreach ($all_playlists as $playlist) {
            if ($playlist->getIsEnabled() && $playlist->getType() === 'default') {
                $total_weights += $playlist->getWeight();
            }
        }

        $station_tz = $station->getTimezone();
        $now = Chronos::now(new DateTimeZone($station_tz));

        $playlists = [];

        $songs_query = $this->em->createQuery(/** @lang DQL */ '
            SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
            FROM App\Entity\StationMedia sm
            JOIN sm.playlists spm
            WHERE spm.playlist = :playlist');

        foreach ($all_playlists as $playlist) {
            $playlist_row = $this->playlist_repo->toArray($playlist);

            if ($playlist->getIsEnabled()) {
                if (Entity\StationPlaylist::TYPE_DEFAULT === $playlist->getType()) {
                    $playlist_row['probability'] = round(($playlist->getWeight() / $total_weights) * 100, 1) . '%';
                } elseif (Entity\StationPlaylist::TYPE_SCHEDULED === $playlist->getType()) {
                    $schedule_start = Entity\StationPlaylist::getDateTime($playlist->getScheduleStartTime(), $now);
                    $schedule_end = Entity\StationPlaylist::getDateTime($playlist->getScheduleEndTime(), $now);

                    $playlist_row['schedule_start'] = $schedule_start->toIso8601String();
                    $playlist_row['schedule_end'] = $schedule_end->toIso8601String();
                }
            }

            $song_totals = $songs_query->setParameter('playlist', $playlist)
                ->getArrayResult();

            $playlist_row['num_songs'] = (int)$song_totals[0]['num_songs'];
            $playlist_row['total_length'] = (int)$song_totals[0]['total_length'];
            $playlists[$playlist->getId()] = $playlist_row;
        }

        return $request->getView()->renderToResponse($response, 'stations/playlists/index', [
            'playlists' => $playlists,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
            'station_tz' => $station_tz,
            'station_now' => $now->toIso8601String(),
            'schedule_url' => $request->getRouter()->named('stations:playlists:schedule',
                ['station_id' => $station->getId()]),
        ]);
    }


    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . ($id ? __('Playlist updated.') : __('Playlist added.')) . '</b>',
                Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('stations:playlists:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/playlists/edit', [
            'form' => $this->form,
            'title' => $id ? __('Edit Playlist') : __('Add Playlist'),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $this->_doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Playlist deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('stations:playlists:index'));
    }
}
