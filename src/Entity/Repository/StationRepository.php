<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Assets\AlbumArtCustomAsset;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Radio\Frontend\AbstractFrontend;
use App\Service\Flow\UploadedFile;
use Azura\Files\ExtendedFilesystemInterface;
use Closure;
use Psr\Http\Message\UriInterface;

/**
 * @extends Repository<Entity\Station>
 */
final class StationRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly SettingsRepository $settingsRepo
    ) {
        parent::__construct($em);
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
     * @inheritDoc
     */
    public function fetchSelect(
        bool|string $add_blank = false,
        Closure $display = null,
        string $pk = 'id',
        string $order_by = 'name'
    ): array {
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
            foreach ($frontend_adapter->getDefaultMounts($station) as $mount) {
                $this->em->persist($mount);
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
        return $customUrl ?? (new AlbumArtCustomAsset())->getUri();
    }

    public function setFallback(
        Entity\Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= (new StationFilesystems($station))->getConfigFilesystem();

        if (!empty($station->getFallbackPath())) {
            $this->doDeleteFallback($station, $fs);
            $station->setFallbackPath(null);
        }

        $originalPath = $file->getClientFilename();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION);

        $fallbackPath = 'fallback.' . $originalExt;
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $fallbackPath);

        $station->setFallbackPath($fallbackPath);
        $this->em->persist($station);
        $this->em->flush();
    }

    public function doDeleteFallback(
        Entity\Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= (new StationFilesystems($station))->getConfigFilesystem();

        $fallbackPath = $station->getFallbackPath();
        if (empty($fallbackPath)) {
            return;
        }

        $fs->delete($fallbackPath);
    }

    public function clearFallback(
        Entity\Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteFallback($station, $fs);

        $station->setFallbackPath(null);
        $this->em->persist($station);
        $this->em->flush();
    }

    public function setStereoToolConfiguration(
        Entity\Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= (new StationFilesystems($station))->getConfigFilesystem();

        $backendConfig = $station->getBackendConfig();

        if (null !== $backendConfig->getStereoToolConfigurationPath()) {
            $this->doDeleteStereoToolConfiguration($station, $fs);
            $backendConfig->setStereoToolConfigurationPath(null);
        }

        $stereoToolConfigurationPath = 'stereo-tool.sts';
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $stereoToolConfigurationPath);

        $backendConfig->setStereoToolConfigurationPath($stereoToolConfigurationPath);
        $station->setBackendConfig($backendConfig);

        $this->em->persist($station);
        $this->em->flush();
    }

    public function doDeleteStereoToolConfiguration(
        Entity\Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $backendConfig = $station->getBackendConfig();
        if (null === $backendConfig->getStereoToolConfigurationPath()) {
            return;
        }

        $fs ??= (new StationFilesystems($station))->getConfigFilesystem();
        $fs->delete($backendConfig->getStereoToolConfigurationPath());
    }

    public function clearStereoToolConfiguration(
        Entity\Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteStereoToolConfiguration($station, $fs);

        $backendConfig = $station->getBackendConfig();
        $backendConfig->setStereoToolConfigurationPath(null);
        $station->setBackendConfig($backendConfig);

        $this->em->persist($station);
        $this->em->flush();
    }
}
