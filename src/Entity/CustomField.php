<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** @OA\Schema(type="object") */
#[ORM\Entity, ORM\Table(name: 'custom_field')]
#[AuditLog\Auditable]
class CustomField
{
    use Traits\TruncateStrings;

    /** @OA\Property() */
    #[ORM\Column(nullable: false)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id;

    /** @OA\Property() */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $name;

    /** @OA\Property(description="The programmatic name for the field. Can be auto-generated from the full name.") */
    #[ORM\Column(length: 100)]
    protected ?string $short_name;

    /** @OA\Property(description="An ID3v2 field to automatically assign to this value, if it exists in the media file.") */
    #[ORM\Column(length: 100)]
    protected ?string $auto_assign;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @AuditLog\AuditIdentifier()
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->truncateString($name);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName(Station::getStationShortName($name));
        }
    }

    public function getShortName(): ?string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : Station::getStationShortName($this->name);
    }

    public function setShortName(?string $short_name): void
    {
        $short_name = trim($short_name);
        if (!empty($short_name)) {
            $this->short_name = $this->truncateString($short_name, 100);
        }
    }

    public function getAutoAssign(): ?string
    {
        return $this->auto_assign;
    }

    public function hasAutoAssign(): bool
    {
        return !empty($this->auto_assign);
    }

    public function setAutoAssign(?string $auto_assign): void
    {
        $this->auto_assign = $auto_assign;
    }
}
