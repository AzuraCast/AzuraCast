<?php

namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractSettingsForm extends Form
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Settings $settings;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Settings $settings,
        array $formConfig
    ) {
        parent::__construct($formConfig);

        $this->em = $em;
        $this->settings = $settings;
        $this->settingsRepo = $settingsRepo;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getEntityRepository(): Entity\Repository\SettingsRepository
    {
        return $this->settingsRepo;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function process(ServerRequest $request): bool
    {
        // Populate the form with existing values (if they exist).
        $defaults = $this->settingsRepo->fetchArray(false);

        // Use current URI from request if the base URL isn't set.
        if (!isset($defaults[Entity\Settings::BASE_URL])) {
            $currentUri = $request->getUri()->withPath('');
            $defaults[Entity\Settings::BASE_URL] = (string)$currentUri;
        }

        $this->populate($defaults);

        // Handle submission.
        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->settingsRepo->setSettings($data);
            return true;
        }

        return false;
    }
}
