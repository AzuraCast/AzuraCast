<?php

declare(strict_types=1);

// An array of message queue types and the DI classes responsible for handling them.
use App\Message;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task;
use Symfony\Component\Mailer;

return [
    Message\AddNewMediaMessage::class => App\Media\MediaProcessor::class,
    Message\ReprocessMediaMessage::class => App\Media\MediaProcessor::class,
    Message\ProcessCoverArtMessage::class => App\Media\MediaProcessor::class,

    Message\WritePlaylistFileMessage::class => Liquidsoap\PlaylistFileWriter::class,

    Message\BackupMessage::class => Task\RunBackupTask::class,

    Message\GenerateAcmeCertificate::class => App\Service\Acme::class,

    Message\DispatchWebhookMessage::class => App\Webhook\Dispatcher::class,
    Message\TestWebhookMessage::class => App\Webhook\Dispatcher::class,

    Mailer\Messenger\SendEmailMessage::class => Mailer\Messenger\MessageHandler::class,
];
