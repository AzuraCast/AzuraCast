<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Service\SsoService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:sso:cleanup',
    description: 'Clean up expired SSO tokens from the database.'
)]
final class SsoCleanupCommand extends Command
{
    public function __construct(
        private readonly SsoService $ssoService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Show what would be cleaned up without actually deleting tokens'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('SSO Token Cleanup');

        $isDryRun = $input->getOption('dry-run');

        if ($isDryRun) {
            $io->note('Running in dry-run mode - no tokens will be deleted');
        }

        try {
            if ($isDryRun) {
                $expiredCount = $this->ssoService->getExpiredTokenCount();
                $io->info(sprintf('Dry-run mode: Found %d expired SSO tokens that would be cleaned up', $expiredCount));
                $io->success('Dry-run completed - no tokens were deleted');
                return Command::SUCCESS;
            }

            $cleanedCount = $this->ssoService->cleanupExpiredTokens();

            if ($cleanedCount > 0) {
                $io->success(sprintf('Successfully cleaned up %d expired SSO tokens', $cleanedCount));
            } else {
                $io->info('No expired SSO tokens found to clean up');
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Failed to clean up SSO tokens: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
