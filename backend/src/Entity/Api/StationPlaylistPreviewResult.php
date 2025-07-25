<?php

declare(strict_types=1);

namespace App\Entity\Api;

final readonly class StationPlaylistPreviewResult extends Status
{
    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        ?string $formattedMessage = null,
        public array $previewResults = [],
        public int $totalChanges = 0
    ) {
        parent::__construct($success, $message, $formattedMessage);
    }
}
