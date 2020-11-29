<?php

namespace App\Controller\Stations;

use App\Exception\AdvancedFeatureException;
use App\Exception\StationUnsupportedException;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Backend\Liquidsoap;
use App\Session\Flash;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class EditLiquidsoapConfigController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Settings $settings
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$settings->enableAdvancedFeatures()) {
            throw new AdvancedFeatureException();
        }

        if (!($backend instanceof Liquidsoap)) {
            throw new StationUnsupportedException();
        }

        $configSections = [
            Liquidsoap\ConfigWriter::CUSTOM_TOP,
            Liquidsoap\ConfigWriter::CUSTOM_PRE_PLAYLISTS,
            Liquidsoap\ConfigWriter::CUSTOM_PRE_FADE,
            Liquidsoap\ConfigWriter::CUSTOM_PRE_LIVE,
            Liquidsoap\ConfigWriter::CUSTOM_PRE_BROADCAST,
        ];

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

        $settings = $station->getBackendConfig();
        $form = new Form($formConfig, ['backend_config' => $settings->toArray()]);

        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            $data = $form->getValues();

            foreach ($data['backend_config'] as $configKey => $configValue) {
                $settings[$configKey] = $configValue;
            }

            $station->setBackendConfig($settings);

            $em->persist($station);
            $em->flush();

            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Edit Liquidsoap Configuration'),
        ]);
    }
}
