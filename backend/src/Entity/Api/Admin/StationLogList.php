<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\LogType;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_StationLogList',
    required: ['*'],
    type: 'object'
)]
final readonly class StationLogList
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property]
        public string $name,
        #[OA\Property(items: new OA\Items(type: LogType::class))]
        public array $logs
    ) {
    }
}
