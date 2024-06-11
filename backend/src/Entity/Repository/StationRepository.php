<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Assets\AssetTypes;
use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationHlsStream;
use App\Entity\StationMount;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Radio\Enums\StreamFormats;
use App\Service\Flow\UploadedFile;
use Closure;
use Psr\Http\Message\UriInterface;

/**
 * @extends Repository<Station>
 */
final class StationRepository extends Repository
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    protected string $entityClass = Station::class;

    /**
     * @param string $identifier A numeric or string identifier for a station.
     */
    public function findByIdentifier(string $identifier): ?Station
    {
        return is_numeric($identifier)
            ? $this->repository->find($identifier)
            : $this->repository->findOneBy(['short_name' => $identifier]);
    }

    public function getActiveCount(): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
            SELECT COUNT(s.id) FROM App\Entity\Station s WHERE s.is_enabled = 1
            DQL
        )->getSingleScalarResult();
    }

    /**
     * @return array<array-key, Station>
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
        bool|string $addBlank = false,
        Closure $display = null,
        string $pk = 'id',
        string $orderBy = 'name'
    ): array {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($addBlank !== false) {
            $select[''] = ($addBlank === true) ? 'Select...' : $addBlank;
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
     * @return iterable<Station>
     */
    public function iterateEnabledStations(): iterable
    {
        return $this->em->createQuery(
            <<<DQL
            SELECT s FROM App\Entity\Station s WHERE s.is_enabled = 1
            DQL
        )->toIterable();
    }

    /**
     * Reset mount points to their adapter defaults (in the event of an adapter change).
     */
    public function resetMounts(Station $station): void
    {
        foreach ($station->getMounts() as $mount) {
            $this->em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($station->getFrontendType()->supportsMounts()) {
            $record = new StationMount($station);
            $record->setName('/radio.mp3');
            $record->setIsDefault(true);
            $record->setEnableAutodj(true);
            $record->setAutodjFormat(StreamFormats::Mp3);
            $record->setAutodjBitrate(128);
            $this->em->persist($record);
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    public function resetHls(Station $station): void
    {
        foreach ($station->getHlsStreams() as $hlsStream) {
            $this->em->remove($hlsStream);
        }

        if ($station->getEnableHls() && $station->getBackendType()->isEnabled()) {
            $streams = [
                'aac_lofi' => 48,
                'aac_midfi' => 96,
                'aac_hifi' => 192,
            ];

            foreach ($streams as $name => $bitrate) {
                $record = new StationHlsStream($station);
                $record->setName($name);
                $record->setFormat(StreamFormats::Aac);
                $record->setBitrate($bitrate);
                $this->em->persist($record);
            }
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    public function flushRelatedMedia(Station $station): void
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
     * @param Station|null $station
     */
    public function getDefaultAlbumArtUrl(?Station $station = null): UriInterface
    {
        if (null !== $station) {
            $stationAlbumArt = AssetTypes::AlbumArt->createObject($this->environment, $station);
            if ($stationAlbumArt->isUploaded()) {
                return $stationAlbumArt->getUri();
            }

            $stationCustomUri = $station->getBrandingConfig()->getDefaultAlbumArtUrlAsUri();
            if (null !== $stationCustomUri) {
                return $stationCustomUri;
            }
        }

        $customUrl = $this->readSettings()->getDefaultAlbumArtUrlAsUri();
        return $customUrl ?? AssetTypes::AlbumArt->createObject($this->environment)->getUri();
    }

    public function setFallback(
        Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

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
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

        $fallbackPath = $station->getFallbackPath();
        if (empty($fallbackPath)) {
            return;
        }

        $fs->delete($fallbackPath);
    }

    public function clearFallback(
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteFallback($station, $fs);

        $station->setFallbackPath(null);
        $this->em->persist($station);
        $this->em->flush();
    }

    public function setStereoToolConfiguration(
        Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

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
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $backendConfig = $station->getBackendConfig();
        if (null === $backendConfig->getStereoToolConfigurationPath()) {
            return;
        }

        $fs ??= StationFilesystems::buildConfigFilesystem($station);
        $fs->delete($backendConfig->getStereoToolConfigurationPath());
    }

    public function clearStereoToolConfiguration(
        Station $station,
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
