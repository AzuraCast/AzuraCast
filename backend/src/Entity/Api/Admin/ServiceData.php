<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServiceData',
    required: ['*'],
    type: 'object'
)]
final class ServiceData
{
    use HasLinks;

    public function __construct(
        #[OA\Property]
        public readonly string $name,
        #[OA\Property]
        public readonly string $description,
        #[OA\Property]
        public readonly bool $running
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'running' => $this->running,
        ];
    }
}
