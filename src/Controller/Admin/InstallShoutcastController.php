<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Config;
use App\Environment;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\SHOUTcast;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Process\Process;

class InstallShoutcastController
{
    protected array $form_config;

    public function __construct(
        protected SHOUTcast $adapter,
        Config $config
    ) {
        $this->form_config = $config->get('forms/install_shoutcast');
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment
    ): ResponseInterface {
        $form_config = $this->form_config;

        $version = $this->adapter->getVersion();

        if (null !== $version) {
            $form_config['groups'][0]['elements']['current_version'][1]['markup'] = '<p class="text-success">' . __(
                'SHOUTcast version "%s" is currently installed.',
                $version
            ) . '</p>';
        }

        $form = new Form($form_config, []);

        if ($form->isValid($request)) {
            try {
                $sc_base_dir = $environment->getParentDirectory() . '/servers/shoutcast2';

                $values = $form->getValues();

                $import_file = $values['binary'] ?? null;
                if ($import_file instanceof UploadedFileInterface) {
                    $sc_tgz_path = $sc_base_dir . '/sc_serv.tar.gz';
                    if (is_file($sc_tgz_path)) {
                        unlink($sc_tgz_path);
                    }

                    $import_file->moveTo($sc_tgz_path);

                    $process = new Process(
                        [
                            'tar',
                            'xvzf',
                            $sc_tgz_path,
                        ],
                        $sc_base_dir
                    );

                    $process->mustRun();

                    unlink($sc_tgz_path);
                }

                return $response->withRedirect($request->getUri()->getPath());
            } catch (Exception $e) {
                $form
                    ->getField('binary')
                    ->addError(get_class($e) . ': ' . $e->getMessage());
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $form,
                'render_mode' => 'edit',
                'title' => __('Install SHOUTcast'),
            ]
        );
    }
}
