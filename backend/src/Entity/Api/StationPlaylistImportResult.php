<?php

declare(strict_types=1);

namespace App\Entity\Api;

final class StationPlaylistImportResult extends Status
{
    public array $import_results = [];

    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        ?string $formattedMessage = null,
        array $importResults = [],
    ) {
        parent::__construct($success, $message, $formattedMessage);

        $this->import_results = $importResults;
    }
}
