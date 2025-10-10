<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Station;
use App\Entity\StationHlsStream;
use App\Entity\StationMount;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Radio\Enums\StreamFormats;
use App\Service\Flow\UploadedFile;
use Closure;

/**
 * @extends Repository<Station>
 */
final class StationRepository extends Repository
{
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
        ?Closure $display = null,
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
        foreach ($station->mounts as $mount) {
            $this->em->remove($mount);
        }

        // Create default mountpoints if station supports them.
        if ($station->frontend_type->supportsMounts()) {
            $record = new StationMount($station);
            $record->name = '/radio.mp3';
            $record->is_default = true;
            $record->enable_autodj = true;
            $record->autodj_format = StreamFormats::Mp3;
            $record->autodj_bitrate = $station->max_bitrate !== 0 ? $station->max_bitrate : 192;
            $this->em->persist($record);
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    public function resetHls(Station $station): void
    {
        foreach ($station->hls_streams as $hlsStream) {
            $this->em->remove($hlsStream);
        }

        if ($station->enable_hls && $station->backend_type->isEnabled()) {
            $streams = [
                'aac_lofi' => 48,
                'aac_midfi' => 96,
                'aac_hifi' => $station->max_bitrate !== 0 ? $station->max_bitrate : 192,
            ];

            foreach ($streams as $name => $bitrate) {
                $record = new StationHlsStream($station);
                $record->name = $name;
                $record->bitrate = $bitrate;
                $this->em->persist($record);
            }
        }

        $this->em->flush();
        $this->em->refresh($station);
    }

    public function reduceMountsBitrateToLimit(Station $station): void
    {
        foreach ($station->mounts as $mount) {
            if ($mount->autodj_bitrate > $station->max_bitrate) {
                $mount->autodj_bitrate = $station->max_bitrate;
                $this->em->persist($mount);
            }
        }

        $this->em->flush();
    }

    public function reduceHlsBitrateToLimit(Station $station): void
    {
        foreach ($station->hls_streams as $hlsStream) {
            if ($hlsStream->bitrate > $station->max_bitrate) {
                $hlsStream->bitrate = $station->max_bitrate;
                $this->em->persist($hlsStream);
            }
        }

        $this->em->flush();
    }

    public function reduceRemoteRelayAutoDjBitrateToLimit(Station $station): void
    {
        foreach ($station->remotes as $remoteRelay) {
            if ($remoteRelay->autodj_bitrate > $station->max_bitrate) {
                $remoteRelay->autodj_bitrate = $station->max_bitrate;
                $this->em->persist($remoteRelay);
            }
        }

        $this->em->flush();
    }

    public function reduceLiveBroadcastRecordingBitrateToLimit(Station $station): void
    {
        $backendConfig = $station->backend_config;
        if ($backendConfig->record_streams_bitrate > $station->max_bitrate) {
            $backendConfig->record_streams_bitrate = $station->max_bitrate;
            $station->backend_config = $backendConfig;
            $this->em->persist($station);
        }

        $this->em->flush();
    }

    public function reduceMountPointsToLimit(Station $station): void
    {
        if ($station->max_mounts === 0) {
            return;
        }

        foreach ($station->mounts as $index => $stationMount) {
            if (($index + 1) > $station->max_mounts) {
                $this->em->remove($stationMount);
            }
        }

        $this->em->flush();
    }

    public function reduceHlsStreamsToLimit(Station $station): void
    {
        if ($station->max_hls_streams === 0) {
            return;
        }

        foreach ($station->hls_streams as $index => $stationHlsStream) {
            if (($index + 1) > $station->max_hls_streams) {
                $this->em->remove($stationHlsStream);
            }
        }

        $this->em->flush();
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
                WHERE IDENTITY(spm.playlist) IN (
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

    public function setFallback(
        Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

        if (!empty($station->fallback_path)) {
            $this->doDeleteFallback($station, $fs);
            $station->fallback_path = null;
        }

        $originalPath = $file->getClientFilename();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION);

        $fallbackPath = 'fallback.' . $originalExt;
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $fallbackPath);

        $station->fallback_path = $fallbackPath;
        $this->em->persist($station);
        $this->em->flush();
    }

    public function doDeleteFallback(
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

        $fallbackPath = $station->fallback_path;
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

        $station->fallback_path = null;
        $this->em->persist($station);
        $this->em->flush();
    }

    public function setStereoToolConfiguration(
        Station $station,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($station);

        $backendConfig = $station->backend_config;

        if (null !== $backendConfig->stereo_tool_configuration_path) {
            $this->doDeleteStereoToolConfiguration($station, $fs);
            $backendConfig->stereo_tool_configuration_path = null;
        }

        $stereoToolConfigurationPath = 'stereo-tool.sts';
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $stereoToolConfigurationPath);

        $backendConfig->stereo_tool_configuration_path = $stereoToolConfigurationPath;
        $station->backend_config = $backendConfig;

        $this->em->persist($station);
        $this->em->flush();
    }

    public function doDeleteStereoToolConfiguration(
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $backendConfig = $station->backend_config;
        $configPath = $backendConfig->stereo_tool_configuration_path;

        if (null === $configPath) {
            return;
        }

        $fs ??= StationFilesystems::buildConfigFilesystem($station);
        $fs->delete($configPath);
    }

    public function clearStereoToolConfiguration(
        Station $station,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteStereoToolConfiguration($station, $fs);

        $backendConfig = $station->backend_config;
        $backendConfig->stereo_tool_configuration_path = null;
        $station->backend_config = $backendConfig;

        $this->em->persist($station);
        $this->em->flush();
    }
}
