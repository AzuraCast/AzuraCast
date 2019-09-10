<?php
namespace App\Controller\Admin;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Exception\NotFoundException;
use App\Form\Form;
use App\Form\SettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\Task\Backup;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Exception;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

class BackupsController
{
    /** @var SettingsForm */
    protected $settings_form;

    /** @var SettingsRepository */
    protected $settings_repo;

    /** @var Form */
    protected $backup_run_form;

    /** @var Backup */
    protected $backup_task;

    /** @var Filesystem */
    protected $backup_fs;

    /** @var string */
    protected $csrf_namespace = 'admin_backups';

    /**
     * @param EntityManager $em
     * @param Config $config
     * @param Backup $backup_task
     */
    public function __construct(
        EntityManager $em,
        Config $config,
        Backup $backup_task
    ) {
        $settings_form = new SettingsForm($em, $config->get('forms/backup'));
        $backup_run_form = new Form($config->get('forms/backup_run'));

        $this->settings_form = $settings_form;
        $this->settings_repo = $settings_form->getEntityRepository();

        $this->backup_run_form = $backup_run_form;

        $this->backup_task = $backup_task;
        $this->backup_fs = new Filesystem(new Local(Backup::BASE_DIR));
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'admin/backups/index', [
            'backups' => $this->backup_fs->listContents('', false),
            'is_enabled' => (bool)$this->settings_repo->getSetting(Settings::BACKUP_ENABLED, false),
            'last_run' => $this->settings_repo->getSetting(Settings::BACKUP_LAST_RUN, 0),
            'last_result' => $this->settings_repo->getSetting(Settings::BACKUP_LAST_RESULT, 0),
            'last_output' => $this->settings_repo->getSetting(Settings::BACKUP_LAST_OUTPUT, ''),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function configureAction(ServerRequest $request, Response $response): ResponseInterface
    {
        if (false !== $this->settings_form->process($request)) {
            $request->getSession()->flash(__('Changes saved.'), 'green');
            return $response->withRedirect($request->getRouter()->fromHere('admin:backups:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->settings_form,
            'render_mode' => 'edit',
            'title' => __('Configure Backups'),
        ]);
    }

    public function runAction(ServerRequest $request, Response $response): ResponseInterface
    {
        // Handle submission.
        if ('POST' === $request->getMethod() && $this->backup_run_form->isValid($request->getParsedBody())) {
            $data = $this->backup_run_form->getValues();

            [$result_code, $result_output] = $this->backup_task->runBackup($data['path'], $data['exclude_media']);

            $is_successful = (0 === $result_code);

            return $request->getView()->renderToResponse($response, 'admin/backups/run', [
                'title' => __('Run Manual Backup'),
                'path' => $data['path'],
                'is_successful' => $is_successful,
                'output' => $result_output,
            ]);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->backup_run_form,
            'render_mode' => 'edit',
            'title' => __('Run Manual Backup'),
        ]);
    }

    public function downloadAction(ServerRequest $request, Response $response, $path): ResponseInterface
    {
        $path = $this->getFilePath($path);

        $fh = $this->backup_fs->readStream($path);
        $file_meta = $this->backup_fs->getMetadata($path);

        try {
            $file_mime = $this->backup_fs->getMimetype($path);
        } catch (Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return $response->withFileDownload($fh, $path)
            ->withNoCache()
            ->withHeader('Content-Type', $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('X-Accel-Buffering', 'no');
    }

    protected function getFilePath($raw_path)
    {
        $path = base64_decode($raw_path);
        $path = basename($path);

        if (!$this->backup_fs->has($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return $path;
    }

    public function deleteAction(ServerRequest $request, Response $response, $path, $csrf): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf, $this->csrf_namespace);

        $path = $this->getFilePath($path);
        $this->backup_fs->delete($path);

        $request->getSession()->flash('<b>' . __('Backup deleted.') . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->named('admin:backups:index'));
    }

}
