<?php

declare(strict_types=1);

namespace App\Entity\Api;

final class MediaBatchResult extends BatchResult
{
    /** @var string[] */
    public array $files = [];

    /** @var string[] */
    public array $directories = [];

    public ?array $responseRecord = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'files' => $this->files,
            'directories' => $this->directories,
            'record' => $this->responseRecord,
        ];
    }
}
