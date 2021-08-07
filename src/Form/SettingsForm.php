<?php

declare(strict_types=1);

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use App\Version;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        Version $version,
        Config $config
    ) {
        $formConfig = $config->get(
            'forms/settings',
            [
                'settings' => $environment,
                'version' => $version,
            ]
        );

        parent::__construct($settingsRepo, $environment, $em, $serializer, $validator, $formConfig);
    }

    /** @inheritDoc */
    public function process(ServerRequest $request, $record = null): object|bool
    {
        if ('https' !== $request->getUri()->getScheme()) {
            $alwaysUseSsl = $this->getField('always_use_ssl');
            $alwaysUseSsl->setAttribute('disabled', 'disabled');
            $alwaysUseSsl->setOption(
                'description',
                __('Visit this page from a secure connection to enforce secure URLs on all pages.')
            );
        }

        return parent::process($request, $record);
    }
}
