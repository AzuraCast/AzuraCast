<?php
namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Radio\Frontend\SHOUTcast;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\UploadedFile;

class InstallShoutcastController
{
    /** @var array */
    protected $form_config;

    /**
     * @param array $form_config
     * @see \App\Provider\AdminProvider
     */
    public function __construct(array $form_config)
    {
        $this->form_config = $form_config;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $form_config = $this->form_config;

        $version = SHOUTcast::getVersion();

        if (null !== $version) {
            $form_config['elements']['current_version'][1]['markup'] = '<p class="text-success">'.__('SHOUTcast version "%s" is currently installed.', $version).'</p>';
        }

        $form = new \AzuraForms\Form($form_config, []);

        if ($request->isPost() && $form->isValid($_POST)) {
            try
            {
                $sc_base_dir = dirname(APP_INCLUDE_ROOT) . '/servers/shoutcast2';

                $files = $request->getUploadedFiles();
                /** @var UploadedFile $import_file */
                $import_file = $files['binary'];

                if ($import_file->getError() === \UPLOAD_ERR_OK) {
                    $sc_tgz_path = $sc_base_dir.'/sc_serv.tar.gz';
                    if (file_exists($sc_tgz_path)) {
                        unlink($sc_tgz_path);
                    }

                    $import_file->moveTo($sc_tgz_path);

                    $sc_tar_path = $sc_base_dir.'/sc_serv.tar';
                    if (file_exists($sc_tar_path)) {
                        unlink($sc_tar_path);
                    }

                    $sc_tgz = new \PharData($sc_tgz_path);
                    $sc_tgz->decompress();

                    $sc_tar = new \PharData($sc_tar_path);
                    $sc_tar->extractTo($sc_base_dir, null, true);
                }

                return $response->withRedirect($request->getUri()->getPath());
            } catch(\Exception $e) {
                $form
                    ->getField('binary')
                    ->addError(get_class($e).': '.$e->getMessage());
            }
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Install SHOUTcast'),
        ]);
    }
}
