<?php

declare(strict_types=1);

namespace App\Entity\Api\Vue;

use App\Entity\Api\ToastNotification;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Vue_AppGlobals',
    required: ['*'],
    type: 'object'
)]
final class AppGlobals
{
    public function __construct(
        #[OA\Property]
        public string $locale = 'en_US',
        #[OA\Property]
        public string $localeShort = 'en',
        #[OA\Property]
        public string $localeWithDashes = 'en-US',
        #[OA\Property]
        public ?object $timeConfig = null,
        #[OA\Property]
        public ?string $apiCsrf = null,
        #[OA\Property]
        public ?DashboardGlobals $dashboardProps = null,
        #[OA\Property]
        public ?UserGlobals $user = null,
        #[OA\Property(
            items: new OA\Items(
                ref: ToastNotification::class
            )
        )]
        public array $notifications = [],
        #[OA\Property(
            items: new OA\Items(
                type: '{}'
            )
        )]
        public ?array $componentProps = [],
    ) {
    }
}
