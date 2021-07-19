<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSettingsForm extends EntityForm
{
    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        array $options = [],
        ?array $defaults = null
    ) {
        parent::__construct($em, $serializer, $validator, $options, $defaults);

        $this->entityClass = Entity\Settings::class;
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
    public function process(ServerRequest $request, $record = null): object|bool
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
