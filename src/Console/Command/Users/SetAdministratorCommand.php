<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Acl;
use App\Console\Command\CommandAbstract;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetAdministratorCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        Entity\Repository\RolePermissionRepository $perms_repo,
        string $email
    ): int {
        $io->title('Set Administrator');

        $user = $em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof Entity\User) {
            $admin_role = $em->getRepository(Entity\Role::class)
                ->find(Entity\Role::SUPER_ADMINISTRATOR_ROLE_ID);

            if (null === $admin_role) {
                $io->error('Administrator role not found.');
                return 1;
            }

            $perms_repo->setActionsForRole(
                $admin_role,
                [
                    'actions_global' => [
                        Acl::GLOBAL_ALL,
                    ],
                ]
            );

            $user_roles = $user->getRoles();

            if (!$user_roles->contains($admin_role)) {
                $user_roles->add($admin_role);
            }

            $em->persist($user);
            $em->flush();

            $io->text(__(
                'The account associated with e-mail address "%s" has been set as an administrator',
                $user->getEmail()
            ));
            $io->newLine();
            return 0;
        }

        $io->error(__('Account not found.'));
        $io->newLine();
        return 1;
    }
}
