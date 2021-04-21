<?php

namespace App\Form;

use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSettingsForm extends EntityForm
{
    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Environment $environment;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        array $options = [],
        ?array $defaults = null
    ) {
        parent::__construct($em, $serializer, $validator, $options, $defaults);

        $this->entityClass = Entity\Settings::class;
        $this->settingsRepo = $settingsRepo;
        $this->environment = $environment;
    }

    public function getSettingsRepository(): Entity\Repository\SettingsRepository
    {
        return $this->settingsRepo;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /** @inheritDoc */
    public function process(ServerRequest $request, $record = null)
    {
        if (null === $record) {
            $record = $this->settingsRepo->readSettings();
        }

        /** @var Entity\Settings $record */
        if (empty($record->getBaseUrl())) {
            $currentUri = $request->getUri()->withPath('');
            $record->setBaseUrl((string)$currentUri);
        }

        return parent::process($request, $record);
    }
}
