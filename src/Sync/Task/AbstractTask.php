<?php
namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManager;

abstract class AbstractTask
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\SettingsRepository */
    protected $settingsRepo;

    /**
     * @param EntityManager $em
     * @param Entity\Repository\SettingsRepository $settingsRepo
     */
    public function __construct(EntityManager $em, Entity\Repository\SettingsRepository $settingsRepo)
    {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
    }

    abstract public function run($force = false): void;
}
