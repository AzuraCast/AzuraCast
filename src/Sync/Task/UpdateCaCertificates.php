<?php
namespace App\Sync\Task;

use App\Entity;
use Azura\CaCertificates;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class UpdateCaCertificates extends AbstractTask
{
    /** @var CaCertificates */
    protected $caCertificates;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        CaCertificates $caCertificates,
        LoggerInterface $logger
    ) {
        $this->caCertificates = $caCertificates;
        $this->logger = $logger;

        parent::__construct($em, $settingsRepo);
    }

    public function run($force = false): void
    {
        try {
            $this->caCertificates->update();

            $this->logger->info('CA certificates updated.');
        } catch (\Exception $e) {
            $this->logger->error('Could not update CA certificates.', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
