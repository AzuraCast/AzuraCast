<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Table(name="settings")
 * @Entity(repositoryClass="Entity\Repository\SettingsRepository")
 */
class Settings extends \App\Doctrine\Entity
{
    /**
     * @Column(name="setting_key", type="string", length=64)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $setting_key;

    /** @Column(name="setting_value", type="json_array", nullable=true) */
    protected $setting_value;
}