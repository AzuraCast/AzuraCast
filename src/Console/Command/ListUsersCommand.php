<?php
namespace App\Console\Command;

use App\Entity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListUsersCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em
    ) {
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
