<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Exception\ValidationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends Repository<Entity\Settings>
 */
final class SettingsRepository extends Repository
{
    protected string $entityClass = Entity\Settings::class;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly Serializer $serializer,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct($em);
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
            throw ValidationException::fromValidationErrors($errors);
        }

        $this->em->persist($settings);
        $this->em->flush();
    }

    public function fromArray(Entity\Settings $entity, array $source): Entity\Settings
    {
        return $this->serializer->denormalize(
            $source,
            Entity\Settings::class,
            null,
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
            ]
        );
    }

    public function toArray(Entity\Settings $entity): array
    {
        return (array)$this->serializer->normalize(
            $entity
        );
    }
}
