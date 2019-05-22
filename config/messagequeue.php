<?php
// An array of message queue types and the DI classes responsible for handling them.
return [
    \App\Message\AddNewMediaMessage::class        => \App\Sync\Task\Media::class,
    \App\Message\ReprocessMediaMessage::class     => \App\Sync\Task\Media::class,

    \App\Message\UpdateNowPlayingMessage::class => \App\Sync\Task\NowPlaying::class,
];
