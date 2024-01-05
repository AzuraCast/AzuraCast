<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'cache_items'),
]
class CacheItem
{
    /** @var resource $item_id */
    #[
        ORM\Column(type: 'binary', length: 255, nullable: false),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'NONE')
    ]
    protected mixed $item_id;

    /** @var resource $item_data */
    #[ORM\Column(type: 'blob', length: 16777215, nullable: false)]
    protected mixed $item_data;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    protected ?int $item_lifetime;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    protected int $item_time;
}
