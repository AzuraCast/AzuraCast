<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Entity;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

class RunAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
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
                    'title'     => __('Run Manual Backup'),
                    'path'      => $data['path'],
                    'outputLog' => basename($tempFile),
                ]
            );
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form'        => $runForm,
                'render_mode' => 'edit',
                'title'       => __('Run Manual Backup'),
            ]
        );
    }
}
