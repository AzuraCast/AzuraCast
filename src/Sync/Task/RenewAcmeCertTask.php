<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Service\Acme;
use Exception;
use Psr\Log\LoggerInterface;

final class RenewAcmeCertTask extends AbstractTask
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly Acme $acme
    ) {
        parent::__construct($em, $logger);
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
