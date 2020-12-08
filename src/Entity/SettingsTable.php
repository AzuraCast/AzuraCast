<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity()
 */
class SettingsTable
{
    /**
     * @ORM\Column(name="setting_key", type="string", length=64)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @var string
     */
    protected $setting_key;

    /**
     * @ORM\Column(name="setting_value", type="json", nullable=true)
     * @var mixed
     */
    protected $setting_value;

    public function __construct(string $setting_key)
    {
        $this->setting_key = $setting_key;
    }

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

    public function setSettingValue($setting_value): void
    {
        $this->setting_value = $setting_value;
    }
}
