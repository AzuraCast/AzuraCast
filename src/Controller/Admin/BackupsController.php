<?php

namespace App\Controller\Admin;

use App\Config;
use App\Controller\AbstractLogViewerController;
use App\Entity;
use App\Exception\NotFoundException;
use App\Flysystem\Filesystem;
use App\Form\BackupSettingsForm;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\Session\Flash;
use App\Sync\Task\RunBackupTask;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class BackupsController extends AbstractLogViewerController
{
    protected Entity\Settings $settings;

    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    protected RunBackupTask $backupTask;

    protected MessageBus $messageBus;

    protected string $csrfNamespace = 'admin_backups';

    public function __construct(
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        RunBackupTask $backup_task,
        MessageBus $messageBus
    ) {
        $this->storageLocationRepo = $storageLocationRepo;
        $this->settings = $settingsRepo->readSettings();

        $this->backupTask = $backup_task;
        $this->messageBus = $messageBus;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $backups = [];
        $storageLocations = $this->storageLocationRepo->findAllByType(Entity\StorageLocation::TYPE_BACKUP);
        foreach ($storageLocations as $storageLocation) {
            $fs = $storageLocation->getFilesystem();
            foreach ($fs->listContents('', true) as $file) {
                $file['storageLocationId'] = $storageLocation->getId();
                $file['pathEncoded'] = base64_encode($storageLocation->getId() . '|' . $file['path']);
                $backups[] = $file;
            }
        }
        $backups = array_reverse($backups);

        return $request->getView()->renderToResponse(
            $response,
            'admin/backups/index',
            [
                'backups' => $backups,
                'is_enabled' => $this->settings->isBackupEnabled(),
                'last_run' => $this->settings->getBackupLastRun(),
                'last_result' => $this->settings->getBackupLastResult(),
                'last_output' => $this->settings->getBackupLastOutput(),
                'csrf' => $request->getCsrf()->generate($this->csrfNamespace),
            ]
        );
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

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $settingsForm,
                'render_mode' => 'edit',
                'title' => __('Configure Backups'),
            ]
        );
    }

    public function runAction(
        ServerRequest $request,
        Response $response,
        Config $config
    ): ResponseInterface {
        $runForm = new Form(
            $config->get(
                'forms/backup_run',
                [
                    'storageLocations' => $this->storageLocationRepo->fetchSelectByType(
                        Entity\StorageLocation::TYPE_BACKUP,
                        true
                    ),
                ]
            )
        );

        // Handle submission.
        if ($request->isPost() && $runForm->isValid($request->getParsedBody())) {
            $data = $runForm->getValues();

            $tempFile = File::generateTempPath('backup.log');

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

            return $request->getView()->renderToResponse(
                $response,
                'admin/backups/run',
                [
                    'title' => __('Run Manual Backup'),
                    'path' => $data['path'],
                    'outputLog' => basename($tempFile),
                ]
            );
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $runForm,
                'render_mode' => 'edit',
                'title' => __('Run Manual Backup'),
            ]
        );
    }

    public function logAction(
        ServerRequest $request,
        Response $response,
        string $path
    ): ResponseInterface {
        $logPath = File::validateTempPath($path);

        return $this->view($request, $response, $logPath, true);
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
            Entity\StorageLocation::TYPE_BACKUP,
            (int)$storageLocationId
        );


        if (!($storageLocation instanceof Entity\StorageLocation)) {
            throw new \InvalidArgumentException('Invalid storage location.');
        }

        $fs = $storageLocation->getFilesystem();

        if (!$fs->has($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return [$path, $fs];
    }
}
