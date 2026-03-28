<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\CustomAssets;

use App\Assets\CustomAssetFactory;
use App\Controller\SingleActionInterface;

abstract readonly class AbstractCustomAssetAction implements SingleActionInterface
{
    public function __construct(
        protected CustomAssetFactory $customAssetFactory
    ) {
    }
}
