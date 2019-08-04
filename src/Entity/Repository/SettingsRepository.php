<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Ramsey\Uuid\Uuid;

class SettingsRepository extends Repository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Entity\Settings::class));
    }

    /** @var array */
    protected static $settings;

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        foreach ($settings as $setting_key => $setting_value) {
            $this->setSetting($setting_key, $setting_value);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setSetting($key, $value): void
    {
        $record = $this->findOneBy(['setting_key' => $key]);

        if (!($record instanceof Entity\Settings)) {
            $record = new Entity\Settings($key);
        }

        $record->setSettingValue($value);

        $this->_em->persist($record);
        $this->_em->flush($record);

        // Update cached value
        self::$settings[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function deleteSetting($key): void
    {
       $record = $this->findOneBy(['setting_key' => $key]);

       if ($record instanceof Entity\Settings)
       {
           $this->_em->remove($record);
           $this->_em->flush($record);
       }

       unset(self::$settings[$key]);
    }

    /**
     * @param string $key
     * @param mixed|null $default_value
     * @param bool $cached
     * @return mixed|null
     */
    public function getSetting($key, $default_value = null, $cached = true)
    {
        $settings = $this->fetchArray($cached);
        return $settings[$key] ?? $default_value;
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        $all_records_raw = $this->findAll();

        $all_records = [];
        foreach ($all_records_raw as $record) {
            /** @var Entity\Settings $record */
            $all_records[$record->getSettingKey()] = $record->getSettingValue();
        }

        return $all_records;
    }

    /**
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC'): array
    {
        if (!self::$settings || !$cached) {
            $settings_raw = $this->_em->createQuery(/** @lang DQL */'SELECT s FROM App\Entity\Settings s ORDER BY s.setting_key ASC')
                ->getArrayResult();

            self::$settings = [];
            foreach ($settings_raw as $setting) {
                self::$settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return self::$settings;
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
}
