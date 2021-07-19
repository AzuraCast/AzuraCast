<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Config;
use App\Controller\AbstractLogViewerController;
use App\Entity;
use App\Exception\NotFoundException;
use App\Form\BackupSettingsForm;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\Session\Flash;
use App\Sync\Task\RunBackupTask;
use App\Utilities\File;
use Azura\Files\Attributes\FileAttributes;
use Azura\Files\ExtendedFilesystemInterface;
use DI\FactoryInterface;
use InvalidArgumentException;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class BackupsController extends AbstractLogViewerController
{
    protected string $csrfNamespace = 'admin_backups';

    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected RunBackupTask $backup_task,
        protected MessageBus $messageBus
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $backups = [];
        $storageLocations = $this->storageLocationRepo->findAllByType(Entity\StorageLocation::TYPE_BACKUP);
        foreach ($storageLocations as $storageLocation) {
            /** @var StorageAttributes $file */
            foreach ($storageLocation->getFilesystem()->listContents('', true) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                /** @var FileAttributes $file */
                $filename = $file->path();

                $backups[] = [
                    'path' => $filename,
                    'basename' => basename($filename),
                    'pathEncoded' => base64_encode($storageLocation->getId() . '|' . $filename),
                    'timestamp' => $file->lastModified(),
                    'size' => $file->fileSize(),
                    'storageLocationId' => $storageLocation->getId(),
                ];
            }
        }

        uasort(
            $backups,
            static function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            }
        );

        $settings = $this->settingsRepo->readSettings();

        return $request->getView()->renderToResponse(
            $response,
            'admin/backups/index',
            [
                'backups' => $backups,
                'is_enabled' => $settings->getBackupEnabled(),
                'last_run' => $settings->getBackupLastRun(),
                'last_result' => $settings->getBackupLastResult(),
                'last_output' => $settings->getBackupLastOutput(),
                'csrf' => $request->getCsrf()->generate($this->csrfNamespace),
            ]
        );
    }

    public function configureAction(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $settingsForm = $factory->make(BackupSettingsForm::class);

        if (false !== $settingsForm->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect((string)$request->getRouter()->fromHere('admin:backups:index'));
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
        if ($runForm->isValid($request)) {
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
        string $path
    ): ResponseInterface {
        [$path, $fs] = $this->getFile($path);

        /** @var ExtendedFilesystemInterface $fs */
        return $response
            ->withNoCache()
            ->streamFilesystemFile($fs, $path);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $path,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrfNamespace);

        [$path, $fs] = $this->getFile($path);

        /** @var ExtendedFilesystemInterface $fs */
        $fs->delete($path);

        $request->getFlash()->addMessage('<b>' . __('Backup deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->named('admin:backups:index'));
    }

    /**
     * @param string $rawPath
     *
     * @return array{0: string, 1: ExtendedFilesystemInterface}
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
            throw new InvalidArgumentException('Invalid storage location.');
        }

        $fs = $storageLocation->getFilesystem();

        if (!$fs->fileExists($path)) {
            throw new NotFoundException(__('Backup not found.'));
        }

        return [$path, $fs];
    }
}
