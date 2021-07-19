<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em
    ): int {
        $io->title('AzuraCast User Accounts');

        $usersRaw = $em->getRepository(Entity\User::class)
            ->findAll();

        $headers = [
            'E-mail Address',
            'Name',
            'Roles',
            'Created',
        ];

        $users = [];

        foreach ($usersRaw as $row) {
            /** @var Entity\User $row */
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
