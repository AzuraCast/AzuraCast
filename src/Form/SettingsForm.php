<?php

namespace App\Form;

use App\Config;
use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use App\Version;
use Doctrine\ORM\EntityManagerInterface;

class SettingsForm extends AbstractSettingsForm
{
    public function __construct(
        EntityManagerInterface $em,
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

        parent::__construct(
            $em,
            $settingsRepo,
            $environment,
            $formConfig
        );
    }

    public function process(ServerRequest $request): bool
    {
        if ('https' !== $request->getUri()->getScheme()) {
            $alwaysUseSsl = $this->getField('alwaysUseSsl');
            $alwaysUseSsl->setAttribute('disabled', 'disabled');
            $alwaysUseSsl->setOption(
                'description',
                __('Visit this page from a secure connection to enforce secure URLs on all pages.')
            );
        }

        return parent::process($request);
    }
}
