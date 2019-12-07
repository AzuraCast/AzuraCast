<?php
namespace App\Sync\Task;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Adapters;
use Azura\EventDispatcher;
use Azura\Logger;
use Doctrine\ORM\EntityManager;

class RadioRequests extends AbstractTask
{
    protected Adapters $adapters;

    protected EventDispatcher $dispatcher;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationRequestRepository $requestRepo,
        Adapters $adapters,
        EventDispatcher $dispatcher
    ) {
        parent::__construct($em, $settingsRepo);

        $this->requestRepo = $requestRepo;
        $this->dispatcher = $dispatcher;
        $this->adapters = $adapters;
    }

    /**
     * Manually process any requests for stations that use "Manual AutoDJ" mode.
     *
     * @param bool $force
     */
    public function run($force = false): void
    {
        /** @var Entity\Repository\StationRepository $stations */
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            if (!$station->useManualAutoDJ()) {
                continue;
            }

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + random_int(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $requests = $this->em->createQuery(/** @lang DQL */ 'SELECT sr, sm 
                FROM App\Entity\StationRequest sr 
                JOIN sr.track sm
                WHERE sr.played_at = 0 
                AND sr.station_id = :station_id 
                AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $threshold)
                ->execute();

            foreach ($requests as $request) {
                /** @var Entity\StationRequest $request */
                $this->requestRepo->checkRecentPlay($request->getTrack(), $station);
                $this->_submitRequest($station, $request);
                break;
            }
        }
    }

    protected function _submitRequest(Entity\Station $station, Entity\StationRequest $request): bool
    {
        // Send request to the station to play the request.
        $backend = $this->adapters->getBackendAdapter($station);
        if (!method_exists($backend, 'request')) {
            return false;
        }

        // Check for an existing SongHistory record and skip if one exists.
        $sh = $this->em->getRepository(Entity\SongHistory::class)->findOneBy([
            'station' => $station,
            'request' => $request,
        ]);

        if (!$sh instanceof Entity\SongHistory) {
            // Log the item in SongHistory.
            $media = $request->getTrack();

            $sh = new Entity\SongHistory($media->getSong(), $station);
            $sh->setTimestampCued(time());
            $sh->setMedia($media);
            $sh->setRequest($request);
            $sh->sentToAutodj();

            $this->em->persist($sh);
            $this->em->flush($sh);
        }

        // Generate full Liquidsoap annotations
        $event = new AnnotateNextSong($station, $sh);
        $this->dispatcher->dispatch($event);

        $track = $event->buildAnnotations();

        // Queue request with Liquidsoap.
        Logger::getInstance()->debug('Submitting request to AutoDJ.', ['track' => $track]);
        $response = $backend->request($station, $track);

        Logger::getInstance()->debug('AutoDJ request response', ['response' => $response]);

        // Log the request as played.
        $request->setPlayedAt(time());

        $this->em->persist($request);
        $this->em->flush();

        return true;
    }
}
