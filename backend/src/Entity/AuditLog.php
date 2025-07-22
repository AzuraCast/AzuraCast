<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\AuditLogOperations;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'audit_log'),
    ORM\Index(name: 'idx_search', columns: ['class', 'user', 'identifier'])
]
final class AuditLog implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    protected static ?string $currentUser = null;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    public readonly DateTimeImmutable $timestamp;

    #[ORM\Column(type: 'smallint', enumType: AuditLogOperations::class)]
    public readonly AuditLogOperations $operation;

    #[ORM\Column(length: 255)]
    public readonly string $class;

    #[ORM\Column(length: 255)]
    public readonly string $identifier;

    #[ORM\Column(name: 'target_class', length: 255, nullable: true)]
    public readonly ?string $targetClass;

    #[ORM\Column(length: 255, nullable: true)]
    public readonly ?string $target;

    #[ORM\Column(type: 'json')]
    public readonly array $changes;

    #[ORM\Column(length: 255, nullable: true)]
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
}
