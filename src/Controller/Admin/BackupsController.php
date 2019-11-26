<?php
namespace App\Controller\Admin;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Exception\NotFoundException;
use App\Form\BackupSettingsForm;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\Task\Backup;
use Azura\Config;
use Azura\Session\Flash;
use Exception;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface;

class BackupsController
{
    /** @var SettingsRepository */
    protected $settingsRepo;

    /** @var Backup */
    protected $backupTask;

    /** @var Filesystem */
    protected $backupFs;

    /** @var string */
    protected $csrfNamespace = 'admin_backups';

    public function __construct(
        SettingsRepository $settings_repo,
        Backup $backup_task
    ) {
        $this->settingsRepo = $settings_repo;
        $this->backupTask = $backup_task;
        $this->backupFs = new Filesystem(new Local(Backup::BASE_DIR));
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'admin/backups/index', [
            'backups' => $this->backupFs->listContents('', false),
            'is_enabled' => (bool)$this->settingsRepo->getSetting(Settings::BACKUP_ENABLED, false),
            'last_run' => $this->settingsRepo->getSetting(Settings::BACKUP_LAST_RUN, 0),
            'last_result' => $this->settingsRepo->getSetting(Settings::BACKUP_LAST_RESULT, 0),
            'last_output' => $this->settingsRepo->getSetting(Settings::BACKUP_LAST_OUTPUT, ''),
            'csrf' => $request->getCsrf()->generate($this->csrfNamespace),
        ]);
    }

    public function configureAction(
        ServerRequest $request,
        Response $response,
        BackupSettingsForm $settingsForm
    ): ResponseInterface {
        if (false !== $settingsForm->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('admin:backups:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $settingsForm,
            'render_mode' => 'edit',
            'title' => __('Configure Backups'),
        ]);
    }

    public function runAction(
        ServerRequest $request,
        Response $response,
        Config $config
    ): ResponseInterface {
        $runForm = new Form($config->get('forms/backup_run'));

        // Handle submission.
        if ($request->isPost() && $runForm->isValid($request->getParsedBody())) {
            $data = $runForm->getValues();

            [$result_code, $result_output] = $this->backupTask->runBackup($data['path'], $data['exclude_media']);

            $is_successful = (0 === $result_code);

            return $request->getView()->renderToResponse($response, 'admin/backups/run', [
                'title' => __('Run Manual Backup'),
                'path' => $data['path'],
                'is_successful' => $is_successful,
                'output' => $result_output,
            ]);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $runForm,
            'render_mode' => 'edit',
            'title' => __('Run Manual Backup'),
        ]);
    }

    public function downloadAction(
        ServerRequest $request,
        Response $response,
        $path
    ): ResponseInterface {
        $path = $this->getFilePath($path);

        $fh = $this->backupFs->readStream($path);
        $file_meta = $this->backupFs->getMetadata($path);

        try {
            $file_mime = $this->backupFs->getMimetype($path);
        } catch (Exception $e) {
            $file_mime = 'application/octet-stream';
        }

        return $response->withFileDownload($fh, $path)
            ->withNoCache()
            ->withHeader('Content-Type', $file_mime)
            ->withHeader('Content-Length', $file_meta['size'])
            ->withHeader('X-Accel-Buffering', 'no');
    }

    public function deleteAction(ServerRequest $request, Response $response, $path, $csrf): ResponseInterface
    {
        $request->getCsrf()->verify($csrf, $this->csrfNamespace);

        $path = $this->getFilePath($path);
        $this->backupFs->delete($path);

        $request->getFlash()->addMessage('<b>' . __('Backup deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->named('admin:backups:index'));
    }

    protected function getFilePath($raw_path): string
    {
        $path = basename(base64_decode($raw_path));

        if (!$this->backupFs->has($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return $path;
    }
}
