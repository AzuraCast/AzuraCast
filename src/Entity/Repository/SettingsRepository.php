<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsRepository extends Repository
{
    protected ValidatorInterface $validator;

    protected string $entityClass = Entity\Settings::class;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->validator = $validator;
    }

    public function readSettings(): Entity\Settings
    {
        static $settingsId = null;

        if (null !== $settingsId) {
            $settings = $this->repository->find($settingsId);
            if ($settings instanceof Entity\Settings) {
                return $settings;
            }
        }

        $settings = $this->repository->findOneBy([]);

        if (!($settings instanceof Entity\Settings)) {
            $settings = new Entity\Settings();
            $this->em->persist($settings);
            $this->em->flush();
        }

        $settingsId = $settings->getAppUniqueIdentifier();

        return $settings;
    }

    /**
     * @param Entity\Settings|array $settingsObj
     */
    public function writeSettings(Entity\Settings|array $settingsObj): void
    {
        if (is_array($settingsObj)) {
            $settings = $this->readSettings();
            $settings = $this->fromArray($settings, $settingsObj);
        } else {
            $settings = $settingsObj;
        }

        $errors = $this->validator->validate($settingsObj);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($settings);
        $this->em->flush();
    }
}
