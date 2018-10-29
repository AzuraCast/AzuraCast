<?php
namespace App\Sync\Task;

use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use studio24\Rotate;
use Supervisor\Supervisor;

class RotateLiquidsoapLogs extends TaskAbstract
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
     * RotateLiquidsoapLogs constructor.
     * @param Adapters $adapters
     * @param EntityManager $em
     * @param Logger $logger
     * @param Supervisor $supervisor
     */
    public function __construct(
        Adapters $adapters,
        EntityManager $em,
        Logger $logger,
        Supervisor $supervisor)
    {
        $this->adapters = $adapters;
        $this->em = $em;
        $this->logger = $logger;
        $this->supervisor = $supervisor;
    }

    public function run($force = false)
    {
        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        $ls_stations = $station_repo->findBy([
            'backend_type' => Adapters::BACKEND_LIQUIDSOAP,
        ]);

        if (empty($ls_stations)) {
            return;
        }

        foreach ($ls_stations as $station) {
            /** @var Entity\Station $station */
            $this->logger->info('Processing logs for station.', ['id' => $station->getId(), 'name' => $station->getName()]);

            $this->processStation($station);
        }
    }

    public function processStation(Entity\Station $station): void
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
}
