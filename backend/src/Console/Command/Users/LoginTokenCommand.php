<?php

declare(strict_types=1);

namespace App\Console\Command\Users;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Enums\LoginTokenTypes;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Http\RouterInterface;
use App\Utilities\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:account:login-token',
    description: 'Create a unique login token URL for the specified account.',
)]
final class LoginTokenCommand extends CommandAbstract
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly UserLoginTokenRepository $loginTokenRepo,
        private readonly RouterInterface $router,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'user',
            InputArgument::REQUIRED,
            'The user ID or e-mail address of the user.'
        )->addOption(
            'type',
            't',
            InputOption::VALUE_OPTIONAL,
            'Type of login token to create.',
            LoginTokenTypes::default()->value,
            array_map(
                fn(LoginTokenTypes $case): string => $case->value,
                LoginTokenTypes::cases()
            )
        )->addOption(
            'expires',
            'e',
            InputOption::VALUE_OPTIONAL,
            'Number of minutes from now when token expires.',
            30
        )->addOption(
            'comment',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Optional comment for the login token.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generate Account Login Token');

        $userString = Types::string($input->getArgument('user'));
        $user = $this->userRepo->findByIdOrEmail($userString);

        if (!($user instanceof User)) {
            $io->error('Account not found.');
            return 1;
        }

        $type = LoginTokenTypes::tryFrom(
            Types::string($input->getOption('type')),
        ) ?? LoginTokenTypes::default();

        $expiresMinutes = Types::int($input->getOption('expires'), 30);
        $comment = Types::stringOrNull($input->getOption('comment'));

        [$splitToken] = $this->loginTokenRepo->createToken(
            $user,
            $type,
            $comment,
            $expiresMinutes
        );

        $url = $this->router->named(
            routeName: 'account:login-token',
            routeParams: ['token' => $splitToken],
            absolute: true
        );

        $io->text([
            'A new login token has been created. Its URL is:',
            '',
            '    ' . $url,
            '',
            'Log in using this temporary URL and set a new password using the web interface.',
            '',
        ]);
        return 0;
    }
}
