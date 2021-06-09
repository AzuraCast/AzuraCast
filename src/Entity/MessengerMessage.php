<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Internal table used for Symfony Messenger handling.
 * @internal
 */
#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'messenger_messages'),
    ORM\Index(columns: ['queue_name']),
    ORM\Index(columns: ['available_at']),
    ORM\Index(columns: ['delivered_at'])
]
class MessengerMessage
{
    #[ORM\Column(type: 'bigint')]
    #[ORM\Id, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: 'text')]
    protected string $body;

    #[ORM\Column(type: 'text')]
    protected string $headers;

    #[ORM\Column(name: 'queue_name', length: 190)]
    protected string $queueName;

    #[ORM\Column(name: 'created_at')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'available_at')]
    protected DateTime $availableAt;

    #[ORM\Column(name: 'delivered_at', nullable: true)]
    protected ?DateTime $deliveredAt = null;
}
