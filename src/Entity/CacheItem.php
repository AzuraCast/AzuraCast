<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'cache_items')
]
class CacheItem
{
    #[
        ORM\Column(type: 'binary', length: 255, nullable: false),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'NONE')
    ]
    protected string $item_id;

    #[ORM\Column(type: 'blob', length: 16777215, nullable: false)]
    protected string $item_data;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    protected ?int $item_lifetime;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    protected int $item_time;
}
