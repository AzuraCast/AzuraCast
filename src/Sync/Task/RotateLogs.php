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
        $this->_rotateLiquidsoapLog($station);
        $this->_cleanUpIcecastLog($station);
    }

    /**
     * @param Entity\Station $station
     * @throws Rotate\FilenameFormatException
     * @throws \App\Exception\NotFound
     */
    protected function _rotateLiquidsoapLog(Entity\Station $station): void
    {
        if ($station->getBackendType() !== Adapters::BACKEND_LIQUIDSOAP) {
            return;
        }

        $log_context = [
            'id' => $station->getId(),
            'name' => $station->getName(),
        ];

        /** @var Liquidsoap $backend_adapter */
        $backend_adapter = $this->adapters->getBackendAdapter($station);

        try
        {
            $config_path = $station->getRadioConfigDir();

            $rotate = new Rotate\Rotate($config_path . '/liquidsoap.log');
            $rotate->keep(5);
            $rotate->size('5MB');
            $rotate->run();
        }
        catch(Rotate\RotateException $e)
        {
            $this->logger->error('Log rotation exception: '.$e->getMessage(), $log_context);
            return;
        }

        try
        {
            // Send the "USR1" signal to Liquidsoap via Supervisord to have it update its log pointer.
            $backend_supervisor_name = $backend_adapter->getProgramName($station);

            $this->supervisor->signalProcess($backend_supervisor_name, 'USR1');
        }
        catch(\Exception $e)
        {
            $this->logger->error('Supervisor exception: '.$e->getMessage(), $log_context);
            return;
        }
    }

    protected function _cleanUpIcecastLog(Entity\Station $station): void
    {
        $config_path = $station->getRadioConfigDir();

        $finder = new Finder();

        $finder
            ->files()
            ->in($config_path)
            ->name('icecast_*.log.*')
            ->date('before 1 week ago');

        foreach($finder as $file)
        {
            $file_path = $file->getRealPath();
            @unlink($file_path);
        }
    }
}
