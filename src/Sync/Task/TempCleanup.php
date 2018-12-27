<?php
namespace App\Sync\Task;

use App\Radio\Filesystem;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

class TempCleanup extends AbstractTask
{
    const DELETE_THRESHOLD = 43200;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Filesystem $filesystem
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(EntityManager $em, Logger $logger, Filesystem $filesystem)
    {
        parent::__construct($em, $logger);

        $this->filesystem = $filesystem;
    }

    public function run($force = false): void
    {
        $station_repo = $this->em->getRepository(Entity\Station::class);
        $stations = $station_repo->findAll();

        foreach ($stations as $station) {
            $this->cleanUpTempDir($station);
        }
    }

    public function cleanUpTempDir(Entity\Station $station): void
    {
        $fs = $this->filesystem->getForStation($station);
        $fs->flushAllCaches();

        $threshold = time() - self::DELETE_THRESHOLD;

        $deleted = 0;
        $preserved = 0;

        foreach($fs->listContents('temp://', false) as $file) {
            $file_uri = 'temp://'.$file['path'];

            if ($file['timestamp'] < $threshold) {
                if ('file' === $file['type']) {
                    $fs->delete($file_uri);
                } else {
                    $fs->deleteDir($file_uri);
                }

                $deleted++;
            } else {
                $preserved++;
            }
        }

        $this->logger->debug(sprintf('Temp file cleanup for station "%s"', $station->getName()), [
            'deleted' => $deleted,
            'preserved' => $preserved,
        ]);
    }
}
