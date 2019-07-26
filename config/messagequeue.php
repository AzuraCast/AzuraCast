<?php
// An array of message queue types and the DI classes responsible for handling them.
return [
    \App\Message\AddNewMediaMessage::class        => \App\Sync\Task\Media::class,
    \App\Message\ReprocessMediaMessage::class     => \App\Sync\Task\Media::class,

    \App\Message\UpdateNowPlayingMessage::class   => \App\Sync\Task\NowPlaying::class,
    \App\Message\NotifyNChanMessage::class        => \App\Service\NChan::class,

    \App\Message\BackupMessage::class             => \App\Sync\Task\Backup::class,
];
