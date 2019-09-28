<?php
namespace App\Sync\Task;

use App\Entity;
use App\Radio\Adapters;
use App\Settings;
use Azura\CaCertificates;
use Azura\Logger;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use studio24\Rotate;
use Supervisor\Supervisor;
use Symfony\Component\Finder\Finder;

class UpdateCaCertificates extends AbstractTask
{
    /** @var CaCertificates */
    protected $caCertificates;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(EntityManager $em, CaCertificates $caCertificates, LoggerInterface $logger)
    {
        $this->caCertificates = $caCertificates;
        $this->logger = $logger;

        parent::__construct($em);
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
