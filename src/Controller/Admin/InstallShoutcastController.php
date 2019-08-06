<?php
namespace App\Controller\Admin;

use App\Form\Form;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Radio\Frontend\SHOUTcast;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Process\Process;

class InstallShoutcastController
{
    /** @var array */
    protected $form_config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->form_config = $config->get('forms/install_shoutcast');
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $form_config = $this->form_config;

        $version = SHOUTcast::getVersion();

        if (null !== $version) {
            $form_config['groups'][0]['elements']['current_version'][1]['markup'] = '<p class="text-success">'.__('SHOUTcast version "%s" is currently installed.', $version).'</p>';
        }

        $form = new Form($form_config, []);

        if ('POST' === $request->getMethod() && $form->isValid($_POST)) {
            try
            {
                $sc_base_dir = dirname(APP_INCLUDE_ROOT) . '/servers/shoutcast2';

                $files = $request->getUploadedFiles();
                /** @var UploadedFileInterface $import_file */
                $import_file = $files['binary'];

                if ($import_file->getError() === \UPLOAD_ERR_OK) {
                    $sc_tgz_path = $sc_base_dir.'/sc_serv.tar.gz';
                    if (file_exists($sc_tgz_path)) {
                        unlink($sc_tgz_path);
                    }

                    $import_file->moveTo($sc_tgz_path);

                    $process = new Process([
                        'tar',
                        'xvzf',
                        $sc_tgz_path
                    ], $sc_base_dir);

                    $process->mustRun();
                }

                return ResponseHelper::withRedirect($response, $request->getUri()->getPath());
            } catch(\Exception $e) {
                $form
                    ->getField('binary')
                    ->addError(get_class($e).': '.$e->getMessage());
            }
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Install SHOUTcast'),
        ]);
    }
}
