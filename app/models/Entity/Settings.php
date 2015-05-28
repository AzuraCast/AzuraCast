<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;

/**
 * @Table(name="settings")
 * @Entity
 */
class Settings extends \DF\Doctrine\Entity
{
    /**
     * @Column(name="setting_key", type="string", length=64)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $setting_key;

    /** @Column(name="setting_value", type="text", nullable=true) */
    protected $setting_value;
    
    /**
     * Static Functions
     */
    
    public static function setSetting($key, $value, $flush_cache = false)
    {
        $record = self::getRepository()->findOneBy(array('setting_key' => $key));
        
        if (!($record instanceof self))
        {
            $record = new self;
            $record->setting_key = $key;
        }

        $record->setting_value = $value;
        $record->save();

        if ($flush_cache)
            self::clearCache();
    }
    
    public static function getSetting($key, $default_value = NULL, $cached = TRUE)
    {
        $settings = self::fetchArray($cached);

        if (isset($settings[$key]))
            return $settings[$key];
        else
            return $default_value;
    }
    
    public static function fetchAll()
    {
        $all_records_raw = self::getRepository()->findAll();
        
        $all_records = array();
        foreach($all_records_raw as $record)
        {
            $all_records[$record['setting_key']] = $record['setting_value'];
        }
        return $all_records;
    }
    
    public static function fetchArray($cached = true)
    {
        static $settings;

        if (!$settings || !$cached)
        {
            $settings = \DF\Cache::get('all_settings');

            if (!$settings || !$cached)
            {
                $em = self::getEntityManager();
                $settings_raw = $em->createQuery('SELECT s FROM '.__CLASS__.' s ORDER BY s.setting_key ASC')
                    ->getArrayResult();

                $settings = array();
                foreach((array)$settings_raw as $setting)
                {
                    $settings[$setting['setting_key']] = $setting['setting_value'];
                }

                \DF\Cache::save($settings, 'all_settings', array(), 8640);
            }
        }

        return $settings;
    }

    public static function clearCache()
    {
        \DF\Cache::remove('all_settings');
    }
}