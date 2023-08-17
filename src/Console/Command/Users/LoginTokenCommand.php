<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\User;
use App\Http\RouterInterface;
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
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly UserLoginTokenRepository $loginTokenRepo,
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

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($user instanceof User) {
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
