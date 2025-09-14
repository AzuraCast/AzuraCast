<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(
        required: [
            'name',
        ],
        type: 'object'
    ),
    ORM\Entity,
    ORM\Table(name: 'custom_field'),
    Attributes\Auditable
]
final class CustomField implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[
        OA\Property,
        ORM\Column(length: 255),
        Assert\NotBlank
    ]
    public string $name {
        set {
            $this->name = $this->truncateString($value);

            if (empty($this->short_name) && !empty($value)) {
                $this->short_name = self::generateShortName($value);
            }
        }
    }

    #[
        OA\Property(
            description: "The programmatic name for the field. Can be auto-generated from the full name."
        ),
        ORM\Column(length: 100, nullable: false)
    ]
    public string $short_name {
        get => !empty($this->short_name)
            ? $this->short_name
            : self::generateShortName($this->name);
        set => $this->truncateString(trim($value), 100);
    }

    #[
        OA\Property(
            description: "An ID3v2 field to automatically assign to this value, if it exists in the media file."
        ),
        ORM\Column(length: 100, nullable: true)
    ]
    public ?string $auto_assign = null {
        set => $this->truncateNullableString($value, 100);
    }

    /** @var Collection<int, StationMediaCustomField> */
    #[ORM\OneToMany(targetEntity: StationMediaCustomField::class, mappedBy: 'field')]
    public private(set) Collection $media_fields;

    public function __construct()
    {
        $this->media_fields = new ArrayCollection();
    }

    public function __clone(): void
    {
        $this->media_fields = new ArrayCollection();
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
