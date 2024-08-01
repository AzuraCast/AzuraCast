<?php

declare(strict_types=1);

namespace App\Entity\Api;

final class PodcastBatchResult extends BatchResult
{
    /**
     * @var array<array{
     *   id: string,
     *   title: string
     * }>
     */
    public array $episodes = [];

    public ?array $records = null;

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'episodes' => $this->episodes,
            'records' => $this->records,
        ];
    }
}
