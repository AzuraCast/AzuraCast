<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\RolePermissionRepository;
use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:account:set-administrator',
    description: 'Set the account specified as a global administrator.',
)]
final class SetAdministratorCommand extends CommandAbstract
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly RolePermissionRepository $permsRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');

        $io->title('Set Administrator');

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $adminRole = $this->permsRepo->ensureSuperAdministratorRole();

            $userRoles = $user->getRoles();
            if (!$userRoles->contains($adminRole)) {
                $userRoles->add($adminRole);
            }

            $this->em->persist($user);
            $this->em->flush();

            $io->text(
                sprintf(
                    __('The account associated with e-mail address "%s" has been set as an administrator'),
                    $user->getEmail()
                )
            );
            $io->newLine();
            return 0;
        }

        $io->error(__('Account not found.'));
        $io->newLine();
        return 1;
    }
}
