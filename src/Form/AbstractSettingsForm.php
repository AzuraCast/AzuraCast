<?php

namespace App\Form;

use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractSettingsForm extends Form
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsTableRepository $settingsTableRepo;

    protected Environment $environment;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsTableRepository $settingsTableRepo,
        Environment $environment,
        array $formConfig
    ) {
        parent::__construct($formConfig);

        $this->em = $em;
        $this->environment = $environment;
        $this->settingsTableRepo = $settingsTableRepo;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getEntityRepository(): Entity\Repository\SettingsTableRepository
    {
        return $this->settingsTableRepo;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function process(ServerRequest $request): bool
    {
        // Populate the form with existing values (if they exist).
        $defaults = $this->settingsTableRepo->readSettingsArray(false);

        // Use current URI from request if the base URL isn't set.
        if (empty($defaults['baseUrl'])) {
            $currentUri = $request->getUri()->withPath('');
            $defaults['baseUrl'] = (string)$currentUri;
        }

        $this->populate($defaults);

        // Handle submission.
        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->settingsTableRepo->writeSettings($data);
            return true;
        }

        return false;
    }
}
