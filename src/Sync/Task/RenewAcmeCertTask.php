<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Service\Acme;
use Exception;

final class RenewAcmeCertTask extends AbstractTask
{
    public function __construct(
        private readonly Acme $acme
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '3 */6 * * *';
    }

    public function run(bool $force = false): void
    {
        try {
            $this->acme->getCertificate();
        } catch (Exception $e) {
            $this->logger->warning(
                sprintf('ACME Failed: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
        }
    }
}
