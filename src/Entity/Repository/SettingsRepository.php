<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Ramsey\Uuid\Uuid;

class SettingsRepository extends Repository
{
    protected static array $cachedSettings;

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $all_records_raw = $this->repository->findAll();

        $all_records = [];
        foreach ($all_records_raw as $record) {
            /** @var Entity\Settings $record */
            $all_records[$record->getSettingKey()] = $record;
        }

        $changes = [];

        foreach ($settings as $setting_key => $setting_value) {
            if (isset($all_records[$setting_key])) {
                $record = $all_records[$setting_key];
                $prev = $record->getSettingValue();
            } else {
                $record = new Entity\Settings($setting_key);
                $prev = null;
            }

            $record->setSettingValue($setting_value);
            $this->em->persist($record);

            // Update cached value
            self::$cachedSettings[$setting_key] = $setting_value;

            // Include change in audit log.
            if ($prev !== $setting_value) {
                $changes[$setting_key] = [$prev, $setting_value];
            }
        }

        if (!empty($changes)) {
            $auditLog = new Entity\AuditLog(
                Entity\AuditLog::OPER_UPDATE,
                Entity\Settings::class,
                'Settings',
                null,
                null,
                $changes
            );

            $this->em->persist($auditLog);
        }

        $this->em->flush();
    }

    /**
     * @param string $key
     */
    public function deleteSetting($key): void
    {
        $record = $this->repository->findOneBy(['setting_key' => $key]);

        if ($record instanceof Entity\Settings) {
            $this->em->remove($record);
            $this->em->flush();
        }

        unset(self::$cachedSettings[$key]);
    }

    /**
     * @return mixed[]
     */
    public function fetchAll(): array
    {
        $all_records_raw = $this->repository->findAll();

        $all_records = [];
        foreach ($all_records_raw as $record) {
            /** @var Entity\Settings $record */
            $all_records[$record->getSettingKey()] = $record->getSettingValue();
        }

        return $all_records;
    }

    /**
     * Force a clearing of the cache.
     */
    public function clearCache(): void
    {
        // Regenerate cache and flush static value.
        $this->fetchArray(false);
    }

    /**
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     *
     * @return mixed[]
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC'): array
    {
        if (!isset(self::$cachedSettings) || !$cached) {
            $settings_raw = $this->em
                ->createQuery(/** @lang DQL */
                    'SELECT s FROM App\Entity\Settings s ORDER BY s.setting_key ASC'
                )
                ->getArrayResult();

            self::$cachedSettings = [];
            foreach ($settings_raw as $setting) {
                self::$cachedSettings[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return self::$cachedSettings;
    }

    /**
     * @return string A persistent unique identifier for this installation.
     */
    public function getUniqueIdentifier(): string
    {
        $app_uuid = $this->getSetting(Entity\Settings::UNIQUE_IDENTIFIER);

        if (!empty($app_uuid)) {
            return $app_uuid;
        }

        $app_uuid = Uuid::uuid4()->toString();
        $this->setSetting(Entity\Settings::UNIQUE_IDENTIFIER, $app_uuid);

        return $app_uuid;
    }

    /**
     * @param string $key
     * @param mixed|null $default_value
     * @param bool $cached
     *
     * @return mixed|null
     */
    public function getSetting($key, $default_value = null, $cached = true)
    {
        $settings = $this->fetchArray($cached);
        return $settings[$key] ?? $default_value;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setSetting($key, $value): void
    {
        $record = $this->repository->findOneBy(['setting_key' => $key]);

        if (!$record instanceof Entity\Settings) {
            $record = new Entity\Settings($key);
        }

        $record->setSettingValue($value);
        $this->em->persist($record);
        $this->em->flush();

        // Update cached value
        self::$cachedSettings[$key] = $value;
    }
}
