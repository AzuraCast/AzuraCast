<?php
namespace App\Entity;

/**
 * @Table(name="settings")
 * @Entity(repositoryClass="Entity\Repository\SettingsRepository")
 */
class Settings
{
    /**
     * @Column(name="setting_key", type="string", length=64)
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @var string
     */
    protected $setting_key;

    /**
     * @Column(name="setting_value", type="json_array", nullable=true)
     * @var mixed
     */
    protected $setting_value;

    /**
     * Settings constructor.
     * @param string $setting_key
     */
    public function __construct(string $setting_key)
    {
        $this->setting_key = $setting_key;
    }

    /**
     * @return string
     */
    public function getSettingKey(): string
    {
        return $this->setting_key;
    }

    /**
     * @return mixed
     */
    public function getSettingValue()
    {
        return $this->setting_value;
    }

    /**
     * @see getSettingValue
     * @return mixed
     */
    public function getValue()
    {
        return $this->setting_value;
    }

    /**
     * @param mixed $setting_value
     */
    public function setSettingValue($setting_value)
    {
        $this->setting_value = $setting_value;
    }
}
