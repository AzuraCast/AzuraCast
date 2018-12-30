<?php
// An array of message queue types and the DI classes responsible for handling them.
return [
    \App\Message\AddNewMedia::class        => \App\Sync\Task\Media::class,
    \App\Message\ReprocessMedia::class     => \App\Sync\Task\Media::class,
];
