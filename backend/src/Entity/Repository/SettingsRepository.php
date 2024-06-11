<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Settings;
use App\Exception\ValidationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends Repository<Settings>
 */
final class SettingsRepository extends Repository
{
    protected string $entityClass = Settings::class;

    public function __construct(
        private readonly Serializer $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function readSettings(): Settings
    {
        static $settingsId = null;

        if (null !== $settingsId) {
            $settings = $this->repository->find($settingsId);
            if ($settings instanceof Settings) {
                return $settings;
            }
        }

        $settings = $this->repository->findOneBy([]);

        if (!($settings instanceof Settings)) {
            $settings = new Settings();
            $this->em->persist($settings);
            $this->em->flush();
        }

        $settingsId = $settings->getAppUniqueIdentifier();

        return $settings;
    }

    /**
     * @param Settings|array $settingsObj
     */
    public function writeSettings(Settings|array $settingsObj): void
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

    public function fromArray(Settings $entity, array $source): Settings
    {
        return $this->serializer->denormalize(
            $source,
            Settings::class,
            null,
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
            ]
        );
    }

    public function toArray(Settings $entity): array
    {
        return (array)$this->serializer->normalize(
            $entity
        );
    }
}
