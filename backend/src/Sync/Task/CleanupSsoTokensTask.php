<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Service\SsoService;

final class CleanupSsoTokensTask extends AbstractTask
{
    public function __construct(
        private readonly SsoService $ssoService,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '0 2 * * *'; // Run daily at 2 AM
    }

    public function run(bool $force = false): void
    {
        $this->logger->info('Starting SSO token cleanup task');

        try {
            $cleanedCount = $this->ssoService->cleanupExpiredTokens();

            if ($cleanedCount > 0) {
                $this->logger->info('SSO token cleanup completed', [
                    'cleaned_count' => $cleanedCount,
                ]);
            } else {
                $this->logger->debug('SSO token cleanup completed - no expired tokens found');
            }
        } catch (\Exception $e) {
            $this->logger->error('SSO token cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
