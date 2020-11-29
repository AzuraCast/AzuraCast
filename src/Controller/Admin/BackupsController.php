<?php

namespace App\Controller\Admin;

use App\Config;
use App\Controller\AbstractLogViewerController;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Settings;
use App\Entity\StorageLocation;
use App\Exception\NotFoundException;
use App\Flysystem\Filesystem;
use App\Form\BackupSettingsForm;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\Session\Flash;
use App\Sync\Task\Backup;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class BackupsController extends AbstractLogViewerController
{
    protected SettingsRepository $settingsRepo;

    protected StorageLocationRepository $storageLocationRepo;

    protected Backup $backupTask;

    protected MessageBus $messageBus;

    protected string $csrfNamespace = 'admin_backups';

    public function __construct(
        SettingsRepository $settings_repo,
        StorageLocationRepository $storageLocationRepo,
        Backup $backup_task,
        MessageBus $messageBus
    ) {
        $this->settingsRepo = $settings_repo;
        $this->storageLocationRepo = $storageLocationRepo;

        $this->backupTask = $backup_task;
        $this->messageBus = $messageBus;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $backups = [];
        foreach ($this->storageLocationRepo->findAllByType(StorageLocation::TYPE_BACKUP) as $storageLocation) {
            $fs = $storageLocation->getFilesystem();
            foreach ($fs->listContents('', true) as $file) {
                $file['storageLocationId'] = $storageLocation->getId();
                $file['pathEncoded'] = base64_encode($storageLocation->getId() . '|' . $file['path']);
                $backups[] = $file;
            }
        }
        $backups = array_reverse($backups);

        return $request->getView()->renderToResponse($response, 'admin/backups/index', [
            'backups' => $backups,
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
        $runForm = new Form($config->get('forms/backup_run', [
            'storageLocations' => $this->storageLocationRepo->fetchSelectByType(StorageLocation::TYPE_BACKUP, true),
        ]));

        // Handle submission.
        if ($request->isPost() && $runForm->isValid($request->getParsedBody())) {
            $data = $runForm->getValues();

            $tempFile = tempnam('/tmp', 'backup_');

            $storageLocationId = (int)$data['storage_location'];
            if ($storageLocationId <= 0) {
                $storageLocationId = null;
            }

            $message = new BackupMessage();
            $message->storageLocationId = $storageLocationId;
            $message->path = $data['path'];
            $message->excludeMedia = $data['exclude_media'];
            $message->outputPath = $tempFile;

            $this->messageBus->dispatch($message);

            return $request->getView()->renderToResponse($response, 'admin/backups/run', [
                'title' => __('Run Manual Backup'),
                'path' => $data['path'],
                'outputLog' => basename($tempFile),
            ]);
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $runForm,
            'render_mode' => 'edit',
            'title' => __('Run Manual Backup'),
        ]);
    }

    public function logAction(
        ServerRequest $request,
        Response $response,
        $path
    ): ResponseInterface {
        return $this->view($request, $response, '/tmp/' . $path, true);
    }

    public function downloadAction(
        ServerRequest $request,
        Response $response,
        $path
    ): ResponseInterface {
        [$path, $fs] = $this->getFile($path);

        /** @var Filesystem $fs */
        return $fs->streamToResponse($response->withNoCache(), $path);
    }

    public function deleteAction(ServerRequest $request, Response $response, $path, $csrf): ResponseInterface
    {
        $request->getCsrf()->verify($csrf, $this->csrfNamespace);

        [$path, $fs] = $this->getFile($path);

        /** @var Filesystem $fs */
        $fs->delete($path);

        $request->getFlash()->addMessage('<b>' . __('Backup deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->named('admin:backups:index'));
    }

    /**
     * @param string $rawPath
     *
     * @return array{0: string, 1: Filesystem}
     * @throws NotFoundException
     */
    protected function getFile(string $rawPath): array
    {
        $pathStr = base64_decode($rawPath);
        [$storageLocationId, $path] = explode('|', $pathStr);

        $storageLocation = $this->storageLocationRepo->findByType(
            StorageLocation::TYPE_BACKUP,
            (int)$storageLocationId
        );


        if (!($storageLocation instanceof StorageLocation)) {
            throw new \InvalidArgumentException('Invalid storage location.');
        }

        $fs = $storageLocation->getFilesystem();

        if (!$fs->has($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return [$path, $fs];
    }
}
