<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Message;
use App\MessageQueue;
use App\Radio\Filesystem;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FtpUploadCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Entity\Repository\StationRepository $stationRepo,
        LoggerInterface $logger,
        Filesystem $filesystem,
        MessageQueue $messageQueue,
        string $path
    ) {
        $logger->info('FTP file uploaded', ['path' => $path]);

        // Working backwards from the media's path, find the associated station(s) to process.
        $stations = [];
        $all_stations = $stationRepo->fetchAll();

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

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $fs = $filesystem->getForStation($station);
            $fs->flushAllCaches();

            $relative_path = str_replace($station->getRadioMediaDir() . '/', '', $path);

            $message = new Message\AddNewMediaMessage;
            $message->station_id = $station->getId();
            $message->path = $relative_path;
            $messageQueue->produce($message);
        }

        return null;
    }
}
