<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\LogType;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_LogList',
    required: ['*'],
    type: 'object'
)]
final readonly class LogList
{
    public function __construct(
        #[OA\Property(items: new OA\Items(type: LogType::class))]
        public array $globalLogs,
        #[OA\Property(items: new OA\Items(type: StationLogList::class))]
        public array $stationLogs,
    ) {
    }
}
