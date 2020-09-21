<?php
namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;

class ClearCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        DoctrineTransport $doctrineTransport
    ) {
        foreach ($doctrineTransport->all() as $envelope) {
            $doctrineTransport->reject($envelope);
        }

        $io->success('Message queue cleared.');
        return 0;
    }
}
