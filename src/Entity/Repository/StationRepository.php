<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Assets\AssetFactory;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Radio\Frontend\AbstractFrontend;
use Closure;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @extends Repository<Entity\Station>
 */
class StationRepository extends Repository
{
    public function __construct(
        protected SettingsRepository $settingsRepo,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $serializer, $environment, $logger);
    }

    /**
     * @param string $identifier A numeric or string identifier for a station.
     */
    public function findByIdentifier(string $identifier): ?Entity\Station
    {
        return is_numeric($identifier)
            ? $this->repository->find($identifier)
            : $this->repository->findOneBy(['short_name' => $identifier]);
    }

    /**
     */
    public function fetchAll(): mixed
    {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT s FROM App\Entity\Station s ORDER BY s.name ASC
            DQL
        )->execute();
    }

    /**
     * @param bool|string $add_blank
     * @param Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     *
     * @return mixed[]
     */
    public function fetchSelect($add_blank = false, Closure $display = null, $pk = 'id', $order_by = 'name'): array
    {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? 'Select...' : $add_blank;
        }

        // Build query for records.
        // Assemble select values and, if necessary, call $display callback.
        foreach ($this->fetchArray() as $result) {
            $key = $result[$pk];
            $select[$key] = ($display === null) ? $result['name'] : $display($result);
        }

        return $select;
    }

    /**
     * @param string $short_code
     */
    public function findByShortCode(string $short_code): ?Entity\Station
    {
        return $this->repository->findOneBy(['short_name' => $short_code]);
    }

    /**
     * Reset mount points to their adapter defaults (in the event of an adapter change).
     *
     * @param Entity\Station $station
     * @param AbstractFrontend $frontend_adapter
     */
    public function resetMounts(Entity\Station $station, AbstractFrontend $frontend_adapter): void
    {
        foreach ($station->getMounts() as $mount) {
            $this->em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($frontend_adapter->supportsMounts()) {
            // Create default mount points.
            foreach ($frontend_adapter->getDefaultMounts() as $mount_point) {
                $mount_record = new Entity\StationMount($station);
                $this->fromArray($mount_record, $mount_point);

                $this->em->persist($mount_record);
            }
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    public function flushRelatedMedia(Entity\Station $station): void
    {
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\SongHistory sh SET sh.media = null
                WHERE sh.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist_id IN (
                    SELECT sp.id FROM App\Entity\StationPlaylist sp WHERE sp.station = :station
                )
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationQueue sq WHERE sq.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationRequest sr WHERE sr.station = :station
            DQL
        )->setParameter('station', $station)
            ->execute();
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @param Entity\Station|null $station
     */
    public function getDefaultAlbumArtUrl(?Entity\Station $station = null): UriInterface
    {
        if (null !== $station) {
            $stationCustomUri = $station->getDefaultAlbumArtUrlAsUri();
            if (null !== $stationCustomUri) {
                return $stationCustomUri;
            }
        }

        $customUrl = $this->settingsRepo->readSettings()->getDefaultAlbumArtUrlAsUri();
        if (null !== $customUrl) {
            return $customUrl;
        }

        return AssetFactory::createAlbumArt($this->environment)->getUri();
    }
}
