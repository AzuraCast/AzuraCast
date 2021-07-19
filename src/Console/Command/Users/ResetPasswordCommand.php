<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetPasswordCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        string $email
    ): int {
        $io->title('Reset Account Password');

        $user = $em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof Entity\User) {
            $temp_pw = Utilities\Strings::generatePassword(15);

            $user->setNewPassword($temp_pw);
            $user->setTwoFactorSecret();

            $em->persist($user);
            $em->flush();

            $io->text([
                'The account password has been reset. The new temporary password is:',
                '',
                '    ' . $temp_pw,
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
