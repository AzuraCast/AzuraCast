<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:account:list',
    description: 'List all accounts in the system.',
)]
final class ListCommand extends CommandAbstract
{
    use EntityManagerAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('AzuraCast User Accounts');

        $usersRaw = $this->em->getRepository(User::class)
            ->findAll();

        $headers = [
            'E-mail Address',
            'Name',
            'Roles',
            'Created',
        ];

        $users = [];

        foreach ($usersRaw as $row) {
            /** @var User $row */
            $roles = [];
            foreach ($row->getRoles() as $role) {
                $roles[] = $role->getName();
            }

            $users[] = [
                $row->getEmail(),
                $row->getName(),
                implode(', ', $roles),
                gmdate('Y-m-d g:ia', $row->getCreatedAt()),
            ];
        }

        $io->table($headers, $users);
        return 0;
    }
}
