<?php

declare(strict_types=1);

namespace App\Entity\Api;

use JsonSerializable;

final class PodcastBatchResult implements JsonSerializable
{
    /**
     * @var array<array{
     *   id: string,
     *   title: string
     * }>
     */
    public array $episodes = [];

    public array $errors = [];

    public ?array $records = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => empty($this->errors),
            'episodes' => $this->episodes,
            'errors' => $this->errors,
            'records' => $this->records,
        ];
    }
}
