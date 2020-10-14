<?php

namespace App\Controller\Admin;

use App\Config;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\SHOUTcast;
use App\Settings;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Process\Process;

use const UPLOAD_ERR_OK;

class InstallShoutcastController
{
    protected array $form_config;

    public function __construct(Config $config)
    {
        $this->form_config = $config->get('forms/install_shoutcast');
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $form_config = $this->form_config;

        $version = SHOUTcast::getVersion();

        if (null !== $version) {
            $form_config['groups'][0]['elements']['current_version'][1]['markup'] = '<p class="text-success">' . __(
                'SHOUTcast version "%s" is currently installed.',
                $version
            ) . '</p>';
        }

        $form = new Form($form_config, []);

        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            try {
                $sc_base_dir = Settings::getInstance()->getParentDirectory() . '/servers/shoutcast2';

                $files = $request->getUploadedFiles();
                /** @var UploadedFileInterface $import_file */
                $import_file = $files['binary'];

                if (UPLOAD_ERR_OK === $import_file->getError()) {
                    $sc_tgz_path = $sc_base_dir . '/sc_serv.tar.gz';
                    if (file_exists($sc_tgz_path)) {
                        unlink($sc_tgz_path);
                    }

                    $import_file->moveTo($sc_tgz_path);

                    $process = new Process([
                        'tar',
                        'xvzf',
                        $sc_tgz_path,
                    ], $sc_base_dir);

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

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Install SHOUTcast'),
        ]);
    }
}
