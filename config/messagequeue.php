<?php
// An array of message queue types and the DI classes responsible for handling them.
use App\Message;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task;

return [
    Message\AddNewMediaMessage::class => Task\CheckMediaTask::class,
    Message\ReprocessMediaMessage::class => Task\CheckMediaTask::class,

    Message\WritePlaylistFileMessage::class => Liquidsoap\ConfigWriter::class,

    Message\UpdateNowPlayingMessage::class => Task\NowPlayingTask::class,

    Message\BackupMessage::class => Task\RunBackupTask::class,

    Message\RunSyncTaskMessage::class => App\Sync\Runner::class,

    Message\DispatchWebhookMessage::class => App\Webhook\Dispatcher::class,
];
