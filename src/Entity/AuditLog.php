<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="audit_log")
 * @ORM\Entity
 */
class AuditLog
{
    use Traits\TruncateStrings;

    public const OPER_INSERT = 1;
    public const OPER_UPDATE = 2;
    public const OPER_DELETE = 3;

    /** @var string|null */
    protected static $currentUser;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="timestamp", type="integer")
     * @var int
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="operation", type="smallint")
     * @var int
     */
    protected $operation;

    /**
     * @ORM\Column(name="class", type="string", length=255)
     * @var string
     */
    protected $class;

    /**
     * @ORM\Column(name="identifier", type="string", length=255)
     * @var string
     */
    protected $identifier;

    /**
     * @ORM\Column(name="target_class", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $targetClass;

    /**
     * @ORM\Column(name="target", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $target;

    /**
     * @ORM\Column(name="changes", type="array")
     * @var array
     */
    protected $changes;

    /**
     * @ORM\Column(name="user", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $user;

    /**
     * @param int $operation
     * @param string $class
     * @param string $identifier
     * @param string|null $targetClass
     * @param string|null $target
     * @param array $changes
     */
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
        $this->class = $this->_filterClassName($class);
        $this->identifier = $identifier;
        $this->targetClass = $this->_filterClassName($targetClass);
        $this->target = $target;
        $this->changes = $changes;
    }

    /**
     * @param string|null $class The FQDN for a class
     * @return string|null The non-namespaced class name
     */
    protected function _filterClassName(?string $class): ?string
    {
        if (empty($class)) {
            return null;
        }

        $classNameParts = explode('\\', $class);
        return array_pop($classNameParts);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getOperation(): int
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string|null
     */
    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    /**
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * Set the current user for this request (used when creating new entries).
     *
     * @param User|null $user
     */
    public static function setCurrentUser(?User $user = null): void
    {
        self::$currentUser = ($user instanceof User)
            ? $user->getIdentifier()
            : null;
    }
}
