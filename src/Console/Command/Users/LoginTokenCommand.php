<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Http\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoginTokenCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        RouterInterface $router,
        string $email
    ): int {
        $io->title('Generate Account Login Recovery URL');

        $user = $em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof Entity\User) {
            $loginToken = $loginTokenRepo->createToken($user);

            $url = $router->named(
                'account:recover',
                ['token' => $loginToken],
                [],
                true
            );

            $io->text([
                'The account recovery URL is:',
                '',
                '    ' . $url,
                '',
                'Log in using this temporary URL and set a new password using the web interface.',
                '',
            ]);
            return 0;
        }

        $io->error('Account not found.');
        return 1;
    }
}
