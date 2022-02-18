<?php

declare(strict_types=1);

namespace App\Entity\Api;

class StationPlaylistImportResult extends Status
{
    public array $import_results = [];

    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        ?string $formatted_message = null,
        array $import_results = [],
    ) {
        parent::__construct($success, $message, $formatted_message);

        $this->import_results = $import_results;
    }
}
