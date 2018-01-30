<?php
namespace Controller\Api;

use AzuraCast\Acl\StationAcl;
use AzuraCast\Sync\NowPlaying;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class InternalController
{
    /** @var StationAcl */
    protected $acl;

    /** @var EntityManager */
    protected $em;

    /** @var NowPlaying */
    protected $sync_nowplaying;

    /**
     * InternalController constructor.
     * @param StationAcl $acl
     * @param EntityManager $em
     * @param NowPlaying $sync_nowplaying
     */
    public function __construct(StationAcl $acl, EntityManager $em, NowPlaying $sync_nowplaying)
    {
        $this->acl = $acl;
        $this->em = $em;
        $this->sync_nowplaying = $sync_nowplaying;
    }

    public function authAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');
        $this->_checkStationAuth($station, $request->getParam('api_auth'));

        $user = $request->getParam('dj_user');
        $pass = $request->getParam('dj_password');

        if ($user === 'shoutcast') {
            list($user, $pass) = explode(':', $pass);
        }

        if (!$station->getEnableStreamers()) {
            return $response->write('false');
        }

        $fe_config = (array)$station->getFrontendConfig();
        if (!empty($fe_config['source_pw']) && strcmp($fe_config['source_pw'], $pass) === 0) {
            return $response->write('true');
        }

        /** @var Entity\Repository\StationStreamerRepository $streamer_repo */
        $streamer_repo = $this->em->getRepository(Entity\StationStreamer::class);

        if ($streamer_repo->authenticate($station, $user, $pass)) {
            return $response->write('true');
        }

        return $response->write('false');
    }

    public function nextsongAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');
        $this->_checkStationAuth($station, $request->getParam('api_auth'));

        if ($station->getBackendType() !== 'liquidsoap') {
            throw new \App\Exception('Not a LiquidSoap station.');
        }

        /** @var Entity\Repository\SongHistoryRepository $history_repo */
        $history_repo = $this->em->getRepository(Entity\SongHistory::class);

        /** @var Entity\SongHistory|null $sh */
        $sh = $history_repo->getNextSongForStation($station, $request->hasParam('api_auth'));

        if ($sh instanceof Entity\SongHistory) {
            // 'annotate:type=\"song\",album=\"$ALBUM\",display_desc=\"$FULLSHOWNAME\",liq_start_next=\"2.5\",liq_fade_in=\"3.5\",liq_fade_out=\"3.5\":$SONGPATH'
            $song_path = $sh->getMedia()->getFullPath();

            return $response->write('annotate:' . implode(',', $sh->getMedia()->getAnnotations()) . ':' . $song_path);
        }

        $error_mp3_path = (APP_INSIDE_DOCKER) ? '/usr/local/share/icecast/web/error.mp3' : APP_INCLUDE_ROOT . '/resources/error.mp3';
        return $response->write($error_mp3_path);
    }

    public function notifyAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');
        $this->_checkStationAuth($station, $request->getParam('api_auth'));

        $payload = $request->getBody()->getContents();

        if (!APP_IN_PRODUCTION) {
            $log = date('Y-m-d g:i:s')."\n".$station->getName()."\n".$payload."\n\n";
            file_put_contents(APP_INCLUDE_TEMP.'/notify.log', $log, \FILE_APPEND);
        }

        $this->sync_nowplaying->processStation($station, $payload);

        return $response->write('received');
    }

    /**
     * @param Entity\Station $station
     * @param $auth_key
     * @return bool
     * @throws \App\Exception\PermissionDenied
     */
    protected function _checkStationAuth(Entity\Station $station, $auth_key)
    {
        if ($this->acl->isAllowed('view administration', $station->getId())) {
            return true;
        }

        if (!$station->validateAdapterApiKey($auth_key)) {
            throw new \App\Exception\PermissionDenied();
        }
    }
}