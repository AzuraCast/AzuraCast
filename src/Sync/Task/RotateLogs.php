<?php
namespace App\Sync\Task;

use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use studio24\Rotate;
use Supervisor\Supervisor;
use Symfony\Component\Finder\Finder;

class RotateLogs extends AbstractTask
{
    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /** @var Supervisor */
    protected $supervisor;

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Adapters $adapters
     * @param Supervisor $supervisor
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        Adapters $adapters,
        Supervisor $supervisor
    ) {
        parent::__construct($em, $logger);

        $this->adapters = $adapters;
        $this->supervisor = $supervisor;
    }

    public function run($force = false): void
    {
        // Rotate logs for individual stations.
        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        $stations = $station_repo->findAll();
        if (!empty($stations)) {
            foreach ($stations as $station) {
                /** @var Entity\Station $station */
                $this->logger->info('Processing logs for station.', ['id' => $station->getId(), 'name' => $station->getName()]);

                $this->rotateStationLogs($station);
            }
        }

        // Rotate the main AzuraCast log.
        $rotate = new Rotate\Rotate(APP_INCLUDE_TEMP . '/app.log');
        $rotate->keep(5);
        $rotate->size('5MB');
        $rotate->run();
    }

    /**
     * Rotate logs that are not automatically rotated (currently Liquidsoap only).
     *
     * @param Entity\Station $station
     *
     */
    public function rotateStationLogs(Entity\Station $station): void
    {
        $this->_cleanUpIcecastLog($station);
    }

    protected function _cleanUpIcecastLog(Entity\Station $station): void
    {
        $config_path = $station->getRadioConfigDir();

        $finder = new Finder();

        $finder
            ->files()
            ->in($config_path)
            ->name('icecast_*.log.*')
            ->date('before 1 month ago');

        foreach($finder as $file)
        {
            $file_path = $file->getRealPath();
            @unlink($file_path);
        }
    }
}
