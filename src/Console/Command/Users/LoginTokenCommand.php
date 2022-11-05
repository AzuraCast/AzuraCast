<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Http\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:account:login-token',
    description: 'Create a unique login recovery URL for the specified account.',
)]
final class LoginTokenCommand extends CommandAbstract
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\UserLoginTokenRepository $loginTokenRepo,
        private readonly RouterInterface $router,
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

        $io->title('Generate Account Login Recovery URL');

        $user = $this->em->getRepository(Entity\User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof Entity\User) {
            $loginToken = $this->loginTokenRepo->createToken($user);

            $url = $this->router->named(
                routeName: 'account:recover',
                routeParams: ['token' => $loginToken],
                absolute: true
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
