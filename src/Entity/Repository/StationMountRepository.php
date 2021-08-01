<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Service\Flow\UploadedFile;
use Azura\Files\ExtendedFilesystemInterface;

/**
 * @extends Repository<Entity\StationMount>
 */
class StationMountRepository extends Repository
{
    public function find(Entity\Station $station, int $id): ?Entity\StationMount
    {
        return $this->repository->findOneBy(
            [
                'station' => $station,
                'id' => $id,
            ]
        );
    }

    public function setIntro(
        Entity\StationMount $mount,
        UploadedFile $file,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= (new StationFilesystems($mount->getStation()))->getConfigFilesystem();

        if (!empty($mount->getIntroPath())) {
            $this->doDeleteIntro($mount, $fs);
            $mount->setIntroPath(null);
        }

        $originalPath = $file->getOriginalFilename();
        $originalExt = pathinfo($originalPath, PATHINFO_EXTENSION);

        $introPath = 'mount_' . $mount->getIdRequired() . '_intro.' . $originalExt;
        $fs->uploadAndDeleteOriginal($file->getUploadedPath(), $introPath);

        $mount->setIntroPath($introPath);
        $this->em->persist($mount);
        $this->em->flush();
    }

    protected function doDeleteIntro(
        Entity\StationMount $mount,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $fs ??= (new StationFilesystems($mount->getStation()))->getConfigFilesystem();

        $introPath = $mount->getIntroPath();
        if (empty($introPath)) {
            return;
        }

        $fs->delete($introPath);
    }

    public function clearIntro(
        Entity\StationMount $mount,
        ?ExtendedFilesystemInterface $fs = null
    ): void {
        $this->doDeleteIntro($mount, $fs);

        $mount->setIntroPath(null);
        $this->em->persist($mount);
        $this->em->flush();
    }

    public function destroy(
        Entity\StationMount $mount
    ): void {
        $this->doDeleteIntro($mount);

        $this->em->remove($mount);
        $this->em->flush();
    }

    /**
     * @param Entity\Station $station
     *
     * @return mixed[]
     */
    public function getDisplayNames(Entity\Station $station): array
    {
        $mounts = $this->repository->findBy(['station' => $station]);

        $displayNames = [];
        foreach ($mounts as $mount) {
            /** @var Entity\StationMount $mount */
            $displayNames[$mount->getId()] = $mount->getDisplayName();
        }

        return $displayNames;
    }

    /**
     * @param Entity\Station $station
     */
    public function getDefaultMount(Entity\Station $station): ?Entity\StationMount
    {
        $mount = $this->repository->findOneBy(['station_id' => $station->getId(), 'is_default' => true]);

        if ($mount instanceof Entity\StationMount) {
            return $mount;
        }

        // Use the first mount if none is specified as default.
        $mount = $station->getMounts()->first();

        if ($mount instanceof Entity\StationMount) {
            $mount->setIsDefault(true);
            $this->em->persist($mount);
            $this->em->flush();

            return $mount;
        }

        return null;
    }
}
