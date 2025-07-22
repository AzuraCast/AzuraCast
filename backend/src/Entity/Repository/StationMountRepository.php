<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationMount;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Flysystem\StationFilesystems;
use App\Service\Flow\UploadedFile;

/**
 * @extends AbstractStationBasedRepository<StationMount>
 */
final class StationMountRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationMount::class;

    public function setIntro(
        StationMount $mount,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($mount->station);

        if (!empty($mount->intro_path)) {
            $this->doDeleteIntro($mount, $fs);
            $mount->intro_path = null;
        }

        $originalPath = $file->getClientFilename();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION);

        $introPath = 'mount_' . $mount->id . '_intro.' . $originalExt;
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $introPath);

        $mount->intro_path = $introPath;
        $this->em->persist($mount);
        $this->em->flush();
    }

    private function doDeleteIntro(
        StationMount $mount,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= StationFilesystems::buildConfigFilesystem($mount->station);

        $introPath = $mount->intro_path;
        if (empty($introPath)) {
            return;
        }

        $fs->delete($introPath);
    }

    public function clearIntro(
        StationMount $mount,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteIntro($mount, $fs);

        $mount->intro_path = null;
        $this->em->persist($mount);
        $this->em->flush();
    }

    public function destroy(
        StationMount $mount
    ): void {
        $this->doDeleteIntro($mount);

        $this->em->remove($mount);
        $this->em->flush();
    }

    /**
     * @param Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Station $station): array
    {
        $mounts = $this->repository->findBy(['station' => $station]);

        $displayNames = [];

        /** @var StationMount $mount */
        foreach ($mounts as $mount) {
            $displayNames[$mount->id] = $mount->display_name;
        }

        return $displayNames;
    }

    public function getDefaultMount(Station $station): ?StationMount
    {
        $mount = $this->repository->findOneBy(['station' => $station, 'is_default' => true]);

        if ($mount instanceof StationMount) {
            return $mount;
        }

        // Use the first mount if none is specified as default.
        $mount = $station->mounts->first();

        if ($mount instanceof StationMount) {
            $mount->is_default = true;
            $this->em->persist($mount);
            $this->em->flush();

            return $mount;
        }

        return null;
    }
}
