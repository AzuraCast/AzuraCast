<?php

namespace App\Entity\Repository;

use App\Annotations\AuditLog\AuditIgnore;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Exception\ValidationException;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionObject;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsRepository extends Repository
{
    protected static ?Entity\Settings $instance = null;

    protected const CACHE_KEY = 'settings';

    protected const CACHE_TTL = 600;

    protected CacheInterface $cache;

    protected ValidatorInterface $validator;

    protected Reader $annotationReader;

    protected string $entityClass = Entity\SettingsTable::class;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        CacheInterface $cache,
        ValidatorInterface $validator,
        Reader $annotationReader
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->cache = $cache;
        $this->validator = $validator;
        $this->annotationReader = $annotationReader;
    }

    public function readSettings(bool $reload = false): Entity\Settings
    {
        if ($reload || null === self::$instance) {
            self::$instance = $this->arrayToObject($this->readSettingsArray());
        }

        return self::$instance;
    }

    public function clearSettingsInstance(): void
    {
        self::$instance = null;
    }

    /**
     * @return mixed[]
     */
    public function readSettingsArray(): array
    {
        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $allRecords = [];
        foreach ($this->repository->findAll() as $record) {
            /** @var Entity\SettingsTable $record */
            $allRecords[$record->getSettingKey()] = $record->getSettingValue();
        }

        $this->cache->set(self::CACHE_KEY, $allRecords, self::CACHE_TTL);

        return $allRecords;
    }

    /**
     * @param Entity\Settings|array $settingsObj
     */
    public function writeSettings($settingsObj): void
    {
        if (is_array($settingsObj)) {
            $settingsObj = $this->arrayToObject($settingsObj, $this->readSettings(true));
        }

        $errors = $this->validator->validate($settingsObj);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $settings = $this->objectToArray($settingsObj);

        $currentRecords = $this->repository->findAll();
        $allRecords = [];
        foreach ($currentRecords as $record) {
            /** @var Entity\SettingsTable $record */
            $allRecords[$record->getSettingKey()] = $record;
        }

        $changes = [];
        foreach ($settings as $settingKey => $settingValue) {
            if (isset($allRecords[$settingKey])) {
                $record = $allRecords[$settingKey];
                $prev = $record->getSettingValue();
            } else {
                $record = new Entity\SettingsTable($settingKey);
                $prev = null;
            }

            $record->setSettingValue($settingValue);
            $this->em->persist($record);

            // Include change in audit log.
            if ($prev !== $settingValue) {
                $changes[$settingKey] = [$prev, $settingValue];
            }
        }

        if (!empty($changes)) {
            // Ignore any properties tagged with "AuditIgnore".
            $reflectionClass = new ReflectionObject($settingsObj);
            $loggableChanges = array_filter(
                $changes,
                function ($settingKey) use ($reflectionClass) {
                    $reflectionProp = $reflectionClass->getProperty($settingKey);
                    $ignoreAnnotation = $this->annotationReader->getPropertyAnnotation(
                        $reflectionProp,
                        AuditIgnore::class
                    );
                    return (null === $ignoreAnnotation);
                },
                ARRAY_FILTER_USE_KEY
            );

            if (!empty($loggableChanges)) {
                $auditLog = new Entity\AuditLog(
                    Entity\AuditLog::OPER_UPDATE,
                    Entity\SettingsTable::class,
                    'Settings',
                    null,
                    null,
                    $loggableChanges
                );

                $this->em->persist($auditLog);
            }
        }

        $this->em->flush();

        $this->cache->set(self::CACHE_KEY, $settings, self::CACHE_TTL);
    }

    /**
     * @param Entity\Settings $settings
     *
     * @return mixed[]
     */
    protected function objectToArray(Entity\Settings $settings): array
    {
        $reflectionClass = new ReflectionObject($settings);
        $array = $this->serializer->normalize($settings, null);

        // Prevent serializer from returning things that aren't actually properties on the class.
        return array_filter(
            $array,
            function ($key) use ($reflectionClass) {
                return $reflectionClass->hasProperty($key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function arrayToObject(array $settings, ?Entity\Settings $existingSettings = null): Entity\Settings
    {
        $settings = array_filter(
            $settings,
            function ($value) {
                return null !== $value;
            }
        );

        $context = [];
        if (null !== $existingSettings) {
            $context[ObjectNormalizer::OBJECT_TO_POPULATE] = $existingSettings;
        }

        return $this->serializer->denormalize($settings, Entity\Settings::class, null, $context);
    }
}
