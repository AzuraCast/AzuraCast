<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'audit_log'),
    ORM\Index(columns: ['class', 'user', 'identifier'], name: 'idx_search')
]
class AuditLog implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const OPER_INSERT = 1;
    public const OPER_UPDATE = 2;
    public const OPER_DELETE = 3;

    protected static ?string $currentUser = null;

    #[ORM\Column]
    protected int $timestamp;

    #[ORM\Column(type: 'smallint')]
    protected int $operation;

    #[ORM\Column(length: 255)]
    protected string $class;

    #[ORM\Column(length: 255)]
    protected string $identifier;

    #[ORM\Column(name: 'target_class', length: 255, nullable: true)]
    protected ?string $targetClass;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $target;

    #[ORM\Column(type: 'array')]
    protected array $changes;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $user;

    public function __construct(
        int $operation,
        string $class,
        string $identifier,
        ?string $targetClass,
        ?string $target,
        array $changes
    ) {
        $this->timestamp = time();
        $this->user = self::$currentUser;

        $this->operation = $operation;
        $this->class = $this->filterClassName($class) ?? '';
        $this->identifier = $identifier;
        $this->targetClass = $this->filterClassName($targetClass);
        $this->target = $target;
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

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getOperation(): int
    {
        return $this->operation;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @return mixed[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }
}
