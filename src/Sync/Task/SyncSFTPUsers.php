<?php
namespace App\Sync\Task;

use App\Entity;
use App\Service\SFTPGo;
use Doctrine\ORM\EntityManager;

class SyncSFTPUsers extends AbstractTask
{
    protected SFTPGo $sftpgo;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        SFTPGo $sftpgo
    ) {
        parent::__construct($em, $settingsRepo);

        $this->sftpgo = $sftpgo;
    }

    public function run($force = false): void
    {
        if (!SFTPGo::isSupported()) {
            return;
        }

        $this->sftpgo->sync();
    }
}