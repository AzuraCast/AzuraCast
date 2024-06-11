<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\User;
use App\Utilities;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:account:reset-password',
    description: 'Reset the password of the specified account.',
)]
final class ResetPasswordCommand extends CommandAbstract
{
    use EntityManagerAwareTrait;

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = Utilities\Types::string($input->getArgument('email'));

        $io->title('Reset Account Password');

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $tempPw = Utilities\Strings::generatePassword(15);

            $user->setNewPassword($tempPw);
            $user->setTwoFactorSecret();

            $this->em->persist($user);
            $this->em->flush();

            $io->text([
                'The account password has been reset. The new temporary password is:',
                '',
                '    ' . $tempPw,
                '',
                'Log in using this temporary password and set a new password using the web interface.',
                '',
            ]);
            return 0;
        }

        $io->error('Account not found.');
        return 1;
    }
}
