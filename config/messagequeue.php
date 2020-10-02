<?php
// An array of message queue types and the DI classes responsible for handling them.
use App\Message;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task;

return [
    Message\AddNewMediaMessage::class => Task\Media::class,
    Message\ReprocessMediaMessage::class => Task\Media::class,

    Message\WritePlaylistFileMessage::class => Liquidsoap\ConfigWriter::class,

    Message\UpdateNowPlayingMessage::class => Task\NowPlaying::class,

    Message\BackupMessage::class => Task\Backup::class,

    Message\DispatchWebhookMessage::class => App\Webhook\Dispatcher::class,
];
