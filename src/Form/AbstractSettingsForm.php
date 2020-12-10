<?php

namespace App\Form;

use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractSettingsForm extends Form
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Environment $environment;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        array $formConfig
    ) {
        parent::__construct($formConfig);

        $this->em = $em;
        $this->environment = $environment;
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

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function process(ServerRequest $request): bool
    {
        // Populate the form with existing values (if they exist).
        $defaults = $this->settingsRepo->readSettingsArray();

        // Use current URI from request if the base URL isn't set.
        if (empty($defaults['baseUrl'])) {
            $currentUri = $request->getUri()->withPath('');
            $defaults['baseUrl'] = (string)$currentUri;
        }

        $this->populate($defaults);

        // Handle submission.
        if ('POST' === $request->getMethod() && $this->isValid($request->getParsedBody())) {
            $data = $this->getValues();
            $this->settingsRepo->writeSettings($data);
            return true;
        }

        return false;
    }
}
