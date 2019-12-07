<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Settings;
use Doctrine\ORM\EntityManager;

abstract class AbstractSettingsForm extends Form
{
    protected EntityManager $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Settings $settings;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Settings $settings,
        array $formConfig
    ) {
        parent::__construct($formConfig);

        $this->em = $em;
        $this->settings = $settings;
        $this->settingsRepo = $settingsRepo;
    }

    public function getEntityManager(): EntityManager
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
        $this->populate($this->settingsRepo->fetchArray(false));

        // Handle submission.
        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->settingsRepo->setSettings($data);
            return true;
        }

        return false;
    }
}