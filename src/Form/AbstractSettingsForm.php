<?php
namespace App\Form;

use App\Entity;
use App\Http\ServerRequest;
use App\Settings;
use Doctrine\ORM\EntityManager;

abstract class AbstractSettingsForm extends Form
{
    /** @var EntityManager */
    protected EntityManager $em;

    /** @var Entity\Repository\SettingsRepository */
    protected Entity\Repository\SettingsRepository $settingsRepo;

    /** @var Settings */
    protected Settings $settings;

    /**
     * @param EntityManager $em
     * @param Entity\Repository\SettingsRepository $settingsRepo
     * @param Settings $settings
     * @param array $formConfig
     */
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

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    /**
     * @return Entity\Repository\SettingsRepository
     */
    public function getEntityRepository(): Entity\Repository\SettingsRepository
    {
        return $this->settingsRepo;
    }

    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     * @param ServerRequest $request
     *
     * @return bool
     */
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