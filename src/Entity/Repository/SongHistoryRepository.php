<?php
namespace App\Entity\Repository;

use App\ApiUtilities;
use App\Entity;
use App\Doctrine\Repository;
use App\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class SongHistoryRepository extends Repository
{
    /** @var ListenerRepository */
    protected $listenerRepository;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        ListenerRepository $listenerRepository
    ) {
        $this->listenerRepository = $listenerRepository;

        parent::__construct($em, $serializer, $settings, $logger);
    }

    /**
     * @param Entity\Station $station
     * @param ApiUtilities $api_utils
     * @param UriInterface|null $base_url
     *
     * @return Entity\Api\SongHistory[]
     */
    public function getHistoryForStation(
        Entity\Station $station,
        ApiUtilities $api_utils,
        UriInterface $base_url = null
    ): array {
        $num_entries = $station->getApiHistoryItems();

        if ($num_entries === 0) {
            return [];
        }

        $history = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s 
            FROM App\Entity\SongHistory sh JOIN sh.song s LEFT JOIN sh.media sm  
            WHERE sh.station_id = :station_id 
            AND sh.timestamp_end != 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $station->getId())
            ->setMaxResults($num_entries)
            ->execute();

        $return = [];
        foreach ($history as $sh) {
            /** @var Entity\SongHistory $sh */
            if ($sh->showInApis()) {
                $return[] = $sh->api(new Entity\Api\SongHistory, $api_utils, $base_url);
            }
        }

        return $return;
    }

    /**
     * @param Entity\Song $song
     * @param Entity\Station $station
     * @param array $np
     *
     * @return Entity\SongHistory
     */
    public function register(
        Entity\Song $song,
        Entity\Station $station,
        array $np
    ): Entity\SongHistory {
        // Pull the most recent history item for this station.
        $last_sh = $this->em->createQuery(/** @lang DQL */ 'SELECT sh 
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station_id', $station->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $listeners = (int)$np['listeners']['current'];

        if ($last_sh instanceof Entity\SongHistory) {
            if ($last_sh->getSong() === $song) {
                // Updating the existing SongHistory item with a new data point.
                $last_sh->addDeltaPoint($listeners);

                $this->em->persist($last_sh);
                $this->em->flush();

                return $last_sh;
            }

            // Wrapping up processing on the previous SongHistory item (if present).
            $last_sh->setTimestampEnd(time());
            $last_sh->setListenersEnd($listeners);

            // Calculate "delta" data for previous item, based on all data points.
            $last_sh->addDeltaPoint($listeners);

            $delta_points = (array)$last_sh->getDeltaPoints();

            $delta_positive = 0;
            $delta_negative = 0;
            $delta_total = 0;

            for ($i = 1, $iMax = count($delta_points); $i < $iMax; $i++) {
                $current_delta = $delta_points[$i];
                $previous_delta = $delta_points[$i - 1];

                $delta_delta = $current_delta - $previous_delta;
                $delta_total += $delta_delta;

                if ($delta_delta > 0) {
                    $delta_positive += $delta_delta;
                } elseif ($delta_delta < 0) {
                    $delta_negative += abs($delta_delta);
                }
            }

            $last_sh->setDeltaPositive($delta_positive);
            $last_sh->setDeltaNegative($delta_negative);
            $last_sh->setDeltaTotal($delta_total);

            $last_sh->setUniqueListeners($this->listenerRepository->getUniqueListeners($station,
                $last_sh->getTimestampStart(),
                time()));

            $this->em->persist($last_sh);
        }

        // Look for an already cued but unplayed song.
        $sh = $this->getCuedSong($song, $station);

        // Processing a new SongHistory item.
        if (!($sh instanceof Entity\SongHistory)) {
            $sh = new Entity\SongHistory($song, $station);
        }

        $sh->setTimestampStart(time());
        $sh->setListenersStart($listeners);
        $sh->addDeltaPoint($listeners);

        $this->em->persist($sh);
        $this->em->flush();

        return $sh;
    }

    /**
     * @param Entity\Song $song
     * @param Entity\Station $station
     *
     * @return Entity\SongHistory|null
     */
    public function getCuedSong(Entity\Song $song, Entity\Station $station): ?Entity\SongHistory
    {
        return $this->em->createQuery(/** @lang DQL */ 'SELECT sh 
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            AND sh.song_id = :song_id
            AND sh.timestamp_cued != 0
            AND sh.timestamp_start = 0
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('song_id', $song->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
