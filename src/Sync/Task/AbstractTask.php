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
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->settingsRepo = $em->getRepository(Entity\Settings::class);
    }

    abstract public function run($force = false): void;
}
