<?php

declare(strict_types=1);

namespace App\Entity\Api;

final class GenericBatchResult extends BatchResult
{
    /**
     * @var array<array{
     *   id: int,
     *   title: string
     * }>
     */
    public array $records = [];

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'records' => $this->records,
        ];
    }
}
