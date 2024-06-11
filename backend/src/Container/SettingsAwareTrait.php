<?php

declare(strict_types=1);

namespace App\Container;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use DI\Attribute\Inject;

trait SettingsAwareTrait
{
    protected SettingsRepository $settingsRepo;

    #[Inject]
    public function setSettingsRepo(SettingsRepository $settingsRepo): void
    {
        $this->settingsRepo = $settingsRepo;
    }

    public function readSettings(): Settings
    {
        return $this->settingsRepo->readSettings();
    }

    public function writeSettings(Settings|array $settings): void
    {
        $this->settingsRepo->writeSettings($settings);
    }
}
