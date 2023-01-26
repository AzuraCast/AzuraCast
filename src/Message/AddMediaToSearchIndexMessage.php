<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\QueueManagerInterface;

final class AddMediaToSearchIndexMessage extends AbstractUniqueMessage
{
    /** @var int The numeric identifier for the StorageLocation entity. */
    public int $storage_location_id;

    /** @var int[] An array of media IDs to process. */
    public array $media;

    public function getIdentifier(): string
    {
        $messageHash = md5(
            json_encode([
                'id' => $this->storage_location_id,
                'media' => $this->media,
            ], JSON_THROW_ON_ERROR)
        );

        return 'AddMediaToSearchIndexMessage_' . $messageHash;
    }

    public function getQueue(): string
    {
        return QueueManagerInterface::QUEUE_MEDIA;
    }
}
