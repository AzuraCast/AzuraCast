<?php
namespace App\Console\Command\MessageQueue;

use App\Console\Command\CommandAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;

class ListCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        DoctrineTransport $doctrineTransport
    ) {
        $messages = [];

        foreach ($doctrineTransport->all() as $envelope) {
            $message = $envelope->getMessage();

            $messageClass = get_class($message);
            $messages[] = [
                $messageClass,
                json_encode($message, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            ];
        }

        $io->title('Message Queue');
        $io->table([
            'Class',
            'Contents',
        ], $messages);
        
        return 0;
    }
}
