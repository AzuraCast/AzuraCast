<?php

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;

class Dashboard extends NowPlaying
{
    use LoadFromParentObject;
    use HasLinks;
}
