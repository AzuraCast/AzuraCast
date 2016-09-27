<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;

/**
 * @Table(name="settings")
 * @Entity(repositoryClass="Repository\SettingsRepository")
 */
class Settings extends \App\Doctrine\Entity
{
    /**
     * @Column(name="setting_key", type="string", length=64)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $setting_key;

    /** @Column(name="setting_value", type="json", nullable=true) */
    protected $setting_value;

    /**
     * @deprecated
     * @param $settings
     */
    public static function setSettings($settings)
    {
        self::getRepository()->setSettings($settings);
    }

    /**
     * @deprecated
     * @param $key
     * @param $value
     * @param bool $flush_cache
     */
    public static function setSetting($key, $value, $flush_cache = true)
    {
        self::getRepository()->setSetting($key, $value, $flush_cache);
    }

    /**
     * @deprecated
     * @param $key
     * @param null $default_value
     * @param bool $cached
     * @return mixed|null
     */
    public static function getSetting($key, $default_value = NULL, $cached = TRUE)
    {
        return self::getRepository()->getSetting($key, $default_value, $cached);
    }

    /**
     * @deprecated
     * @return array
     */
    public static function fetchAll()
    {
        return self::getRepository()->fetchAll();
    }

    /**
     * @deprecated
     * @param bool $cached
     * @param null $order_by
     * @param string $order_dir
     * @return array
     */
    public static function fetchArray($cached = true, $order_by = NULL, $order_dir = 'ASC')
    {
        return self::getRepository()->fetchArray();
    }

    /**
     * @deprecated
     */
    public static function clearCache()
    {
        // Regenerate cache and flush static value.
        self::fetchArray(false);
    }
}