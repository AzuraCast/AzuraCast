<?php

namespace App\Entity;

use App\Annotations\AuditLog\Auditable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

/**
 * @ORM\Table(name="sftp_user", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="username_idx", columns={"username"})
 * })
 * @ORM\Entity()
 *
 * @Auditable()
 */
class SftpUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="sftp_users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="username", type="string", length=32, nullable=false)
     * @var string
     *
     * @Assert\Length(min=1, max=32)
     * @Assert\NotBlank
     */
    protected $username;

    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $password;

    /**
     * @ORM\Column(name="public_keys", type="text", nullable=true)
     * @var string|null
     */
    protected $publicKeys;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function setPassword(?string $password): void
    {
        if (!empty($password)) {
            $this->password = password_hash($password, PASSWORD_ARGON2ID);
        }
    }

    public function getPublicKeys(): ?string
    {
        return $this->publicKeys;
    }

    /**
     * @return string[]
     */
    public function getPublicKeysArray(): array
    {
        $pubKeysRaw = trim($this->publicKeys);
        if (!empty($pubKeysRaw)) {
            return array_filter(array_map('trim', explode("\n", $pubKeysRaw)));
        }

        return [];
    }

    public function setPublicKeys(?string $publicKeys): void
    {
        $this->publicKeys = $publicKeys;
    }

    public function authenticate(?string $password = null, ?string $pubKey = null): bool
    {
        if (!empty($password)) {
            return password_verify($password, $this->password);
        }

        if (!empty($pubKey)) {
            $pubKeys = $this->getPublicKeysArray();
            return in_array($pubKey, $pubKeys, true);
        }

        return false;
    }
}
