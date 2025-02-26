<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_NetworkInterfaceStats',
    required: ['*'],
    type: 'object'
)]
final readonly class NetworkInterfaceStats
{
    public function __construct(
        #[OA\Property]
        public string $interface_name,
        #[OA\Property]
        public NetworkInterfaceReceived $received,
        #[OA\Property]
        public NetworkInterfaceTransmitted $transmitted
    ) {
    }
}
