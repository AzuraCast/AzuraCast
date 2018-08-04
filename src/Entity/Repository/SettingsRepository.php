<?php
namespace App\Entity\Repository;

use App\Entity;

class SettingsRepository extends BaseRepository
{
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
     * @param $key
     * @param $value
     */
    public function setSetting($key, $value): void
    {
        $record = $this->findOneBy(['setting_key' => $key]);

        if (!($record instanceof Entity\Settings)) {
            $record = new Entity\Settings($key);
        }

        $record->setSettingValue($value);

        $this->_em->persist($record);
        $this->_em->flush();

        // Update cached value
        self::$settings[$key] = $value;
    }

    /**
     * @param $key
     * @param null $default_value
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
    public function fetchAll()
    {
        $all_records_raw = $this->findAll();

        $all_records = [];
        foreach ($all_records_raw as $record) {
            $all_records[$record['setting_key']] = $record['setting_value'];
        }

        return $all_records;
    }

    /**
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public function fetchArray($cached = true, $order_by = null, $order_dir = 'ASC')
    {
        if (!self::$settings || !$cached) {
            $settings_raw = $this->_em->createQuery('SELECT s FROM ' . $this->_entityName . ' s ORDER BY s.setting_key ASC')
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
    public function clearCache()
    {
        // Regenerate cache and flush static value.
        $this->fetchArray(false);
    }
}
