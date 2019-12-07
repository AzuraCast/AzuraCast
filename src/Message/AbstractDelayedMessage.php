<?php
namespace App\Message;

abstract class AbstractDelayedMessage extends AbstractMessage
{
    /** @var int One millisecond in microseconds. */
    public const ONE_MSEC = 1000;

    /** @var int One second in microseconds. */
    public const ONE_SEC = 1000000;

    /** @var int The delay before the message should be processed, in seconds. */
    public int $delay = 0;
}
