<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Exception\AdvancedFeatureException;
use App\Exception\StationUnsupportedException;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class EditLiquidsoapConfigAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        $settings = $settingsRepo->readSettings();
        if (!$settings->getEnableAdvancedFeatures()) {
            throw new AdvancedFeatureException();
        }

        if (!($backend instanceof Liquidsoap)) {
            throw new StationUnsupportedException();
        }

        $configSections = Liquidsoap\ConfigWriter::getCustomConfigurationSections();

        $config = $backend->getEditableConfiguration($station);

        $tokens = Liquidsoap\ConfigWriter::getDividerString();

        $formConfig = [
            'method' => 'post',
            'enctype' => 'multipart/form-data',

            'groups' => [
                'ls_config' => [
                    'elements' => [],
                ],

                'submit_grp' => [
                    'elements' => [
                        'submit' => [
                            'submit',
                            [
                                'type' => 'submit',
                                'label' => __('Save Changes'),
                                'class' => 'btn btn-lg btn-primary',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tok = strtok($config, $tokens);
        $i = 0;

        while ($tok !== false) {
            $tok = trim($tok);
            $i++;

            if (in_array($tok, $configSections, true)) {
                $formConfig['groups']['ls_config']['elements'][$tok] = [
                    'textarea',
                    [
                        'belongsTo' => 'backend_config',
                        'class' => 'text-preformatted',
                        'spellcheck' => 'false',
                    ],
                ];
            } else {
                $formConfig['groups']['ls_config']['elements']['config_section_' . $i] = [
                    'markup',
                    [
                        'markup' => '<pre class="typography-body-1">' . $tok . '</pre>',
                    ],
                ];
            }

            $tok = strtok($tokens);
        }

        $backendConfig = $station->getBackendConfig();
        $form = new Form($formConfig, ['backend_config' => $backendConfig->toArray()]);

        if ($form->isValid($request)) {
            $data = $form->getValues();

            foreach ($data['backend_config'] as $configKey => $configValue) {
                $backendConfig[$configKey] = $configValue;
            }

            $station->setBackendConfig($backendConfig);

            $em->persist($station);
            $em->flush();

            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $form,
                'render_mode' => 'edit',
                'title' => __('Edit Liquidsoap Configuration'),
            ]
        );
    }
}
