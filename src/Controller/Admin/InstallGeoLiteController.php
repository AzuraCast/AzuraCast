<?php
namespace App\Controller\Admin;

use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\GeoLite;
use Azura\Config;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Process\Process;
use const UPLOAD_ERR_OK;

class InstallGeoLiteController
{
    protected array $form_config;

    protected GeoLite $geoLite;

    public function __construct(Config $config, GeoLite $geoLite)
    {
        $this->form_config = $config->get('forms/install_geolite');
        $this->geoLite = $geoLite;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $form_config = $this->form_config;

        $version = $this->geoLite->getVersion();
        if (null !== $version) {
            $form_config['groups'][0]['elements']['current_version'][1]['markup'] = '<p class="text-success">' . __('GeoLite version "%s" is currently installed.',
                    $version) . '</p>';
        }

        $form = new Form($form_config, []);

        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            try {
                $baseDir = dirname($this->geoLite->getDatabasePath());

                $files = $request->getUploadedFiles();
                /** @var UploadedFileInterface $import_file */
                $import_file = $files['binary'];

                if (UPLOAD_ERR_OK === $import_file->getError()) {
                    $tgzPath = $baseDir . '/maxmind.tar.gz';
                    if (file_exists($tgzPath)) {
                        unlink($tgzPath);
                    }

                    $import_file->moveTo($tgzPath);

                    $process = new Process([
                        'tar',
                        'xvzf',
                        $tgzPath,
                        '--strip-components=1',
                    ], $baseDir);

                    $process->mustRun();

                    unlink($tgzPath);
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
            'title' => __('Install GeoLite IP Database'),
        ]);
    }
}
