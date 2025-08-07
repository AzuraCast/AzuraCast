<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Api\Admin\AuditLogChangeset;
use App\Entity\Enums\AuditLogOperations;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'audit_log'),
    ORM\Index(name: 'idx_search', columns: ['class', 'user', 'identifier']),
    OA\Schema(
        required: ['*'],
        properties: [

        ],
        type: 'object'
    )
]
final class AuditLog implements IdentifiableEntityInterface, \JsonSerializable
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    protected static ?string $currentUser = null;

    #[
        ORM\Column(type: 'datetime_immutable', precision: 6),
        OA\Property(type: 'string', format: 'date-time')
    ]
    public readonly DateTimeImmutable $timestamp;

    #[
        ORM\Column(type: 'smallint', enumType: AuditLogOperations::class),
        OA\Property(enum: AuditLogOperations::class)
    ]
    public readonly AuditLogOperations $operation;

    #[OA\Property]
    public string $operationText {
        get => $this->operation->getName();
    }

    #[
        ORM\Column(length: 255),
        OA\Property
    ]
    public readonly string $class;

    #[
        ORM\Column(length: 255),
        OA\Property
    ]
    public readonly string $identifier;

    #[
        ORM\Column(name: 'target_class', length: 255, nullable: true),
        OA\Property
    ]
    public readonly ?string $targetClass;

    #[
        ORM\Column(length: 255, nullable: true),
        OA\Property
    ]
    public readonly ?string $target;

    #[
        ORM\Column(type: 'json'),
        OA\Property(
            items: new OA\Items(
                ref: AuditLogChangeset::class
            )
        )
    ]
    public readonly array $changes;

    #[
        ORM\Column(length: 255, nullable: true),
        OA\Property
    ]
    public readonly ?string $user;

    public function __construct(
        AuditLogOperations $operation,
        string $class,
        string $identifier,
        ?string $targetClass,
        ?string $target,
        array $changes
    ) {
        $this->timestamp = Time::nowUtc();
        $this->user = $this->truncateNullableString(self::$currentUser);

        $this->operation = $operation;
        $this->class = $this->truncateString($this->filterClassName($class) ?? '');
        $this->identifier = $this->truncateString($identifier);
        $this->targetClass = $this->truncateNullableString($this->filterClassName($targetClass));
        $this->target = $this->truncateNullableString($target);
        $this->changes = $changes;
    }

    /**
     * @param string|null $class The FQDN for a class
     *
     * @return string|null The non-namespaced class name
     */
    protected function filterClassName(?string $class): ?string
    {
        if (empty($class)) {
            return null;
        }

        $classNameParts = explode('\\', $class);
        return array_pop($classNameParts);
    }

    /**
     * Set the current user for this request (used when creating new entries).
     *
     * @param User|null $user
     */
    public static function setCurrentUser(?User $user = null): void
    {
        self::$currentUser = (null !== $user)
            ? (string)$user
            : null;
    }

    public function jsonSerialize(): array
    {
        $changes = [];
        foreach ($this->changes as $fieldName => [$fieldPrevious, $fieldNew]) {
            $changes[] = new AuditLogChangeset(
                field: $fieldName,
                from: json_encode(
                    $fieldPrevious,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                ),
                to: json_encode(
                    $fieldNew,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
        }

        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format(Time::JS_ISO8601_FORMAT),
            'operation' => $this->operation->value,
            'operationText' => $this->operationText,
            'class' => $this->class,
            'identifier' => $this->identifier,
            'targetClass' => $this->targetClass,
            'target' => $this->target,
            'user' => $this->user,
            'changes' => $changes,
        ];
    }
}
