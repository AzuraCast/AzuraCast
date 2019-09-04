<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Message;
use App\MessageQueue;
use App\Radio\Filesystem;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FtpUpload extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:ftp-upload')
            ->setDescription('Process a file uploaded in PureFTPD')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path of the newly uploaded file.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // PureFTPD sends the "real" path (with symlinks resolved) to us.
        $path = $input->getArgument('path');

        /** @var Logger $logger */
        $logger = $this->get(Logger::class);

        $logger->info('FTP file uploaded', ['path' => $path]);

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Entity\Repository\StationRepository $station_repo */
        $stations_repo = $em->getRepository(Entity\Station::class);

        // Working backwards from the media's path, find the associated station(s) to process.
        $stations = [];
        $all_stations = $stations_repo->findAll();

        $parts = explode('/', dirname($path));
        for ($i = count($parts); $i >= 1; $i--) {
            $search_path = implode('/', array_slice($parts, 0, $i));

            $stations = array_filter($all_stations, function (Entity\Station $station) use ($search_path) {
                return $search_path === $station->getRadioMediaDir();
            });

            if (!empty($stations)) {
                break;
            }
        }

        /** @var Filesystem $filesystem */
        $filesystem = $this->get(Filesystem::class);

        /** @var MessageQueue $message_queue */
        $message_queue = $this->get(MessageQueue::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $fs = $filesystem->getForStation($station);
            $fs->flushAllCaches();

            $relative_path = str_replace($station->getRadioMediaDir() . '/', '', $path);

            $message = new Message\AddNewMediaMessage;
            $message->station_id = $station->getId();
            $message->path = $relative_path;
            $message_queue->produce($message);
        }

        return null;
    }
}
