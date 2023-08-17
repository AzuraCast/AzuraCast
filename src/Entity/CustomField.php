<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\File;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: 'object'),
    ORM\Entity,
    ORM\Table(name: 'custom_field'),
    Attributes\Auditable
]
class CustomField implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property,
        ORM\Column(length: 255),
        Assert\NotBlank
    ]
    protected string $name;

    #[
        OA\Property(
            description: "The programmatic name for the field. Can be auto-generated from the full name."
        ),
        ORM\Column(length: 100, nullable: false)
    ]
    protected string $short_name;

    #[
        OA\Property(
            description: "An ID3v2 field to automatically assign to this value, if it exists in the media file."
        ),
        ORM\Column(length: 100, nullable: true)
    ]
    protected ?string $auto_assign = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->truncateString($name);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName(self::generateShortName($name));
        }
    }

    public function getShortName(): string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : self::generateShortName($this->name);
    }

    public function setShortName(string $shortName): void
    {
        $shortName = trim($shortName);
        if (!empty($shortName)) {
            $this->short_name = $this->truncateString($shortName, 100);
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

    public function setAutoAssign(?string $autoAssign): void
    {
        $this->auto_assign = $autoAssign;
    }

    public function __toString(): string
    {
        return $this->short_name;
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'custom_field_' . $str
            : $str;
    }
}
