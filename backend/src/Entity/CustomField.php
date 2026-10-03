<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\File;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    OA\Schema(
        required: [
            'name',
        ],
        type: 'object'
    ),
    ORM\Entity,
    ORM\Table(name: 'custom_field'),
    Attributes\Auditable,
    AppAssert\UniqueEntity(fields: ['auto_assign'], ignoreNull: true)
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
    public string $short_name = '' {
        set => $this->truncateString(trim($value), 100);
    }

    #[
        OA\Property(
            description: "The media file tag this field is read from on import and written back to when the media is saved."
        ),
        ORM\Column(length: 100, nullable: true),
        Assert\Regex(
            pattern: '/^[\x20-\x3C\x3E-\x7D]+$/D', // See james-heinrich/getid3 VorbisComment::CleanVorbisCommentName
            message: 'Tag names may only contain printable ASCII characters, except "=".'
        )
    ]
    public ?string $auto_assign = null {
        set => $this->truncateNullableString($value, 100, true);
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

    #[Assert\Callback]
    public function hasValidAutoAssign(ExecutionContextInterface $context): void
    {
        if (
            $this->auto_assign !== null
            && in_array(mb_strtolower($this->auto_assign), StationMediaMetadata::getFields(), true)
        ) {
            $context->buildViolation(__('This tag name is reserved for AzuraCast playback metadata.'))
                ->atPath('auto_assign')
                ->addViolation();
        }
    }

    public static function generateShortName(string $str): string
    {
        $str = File::sanitizeFileName($str);

        return (is_numeric($str))
            ? 'custom_field_' . $str
            : $str;
    }
}
