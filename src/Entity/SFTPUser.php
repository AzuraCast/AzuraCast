<?php
namespace App\Entity;

use App\Annotations\AuditLog\Auditable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="sftp_user", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="username_idx", columns={"username"})
 * })
 * @ORM\Entity()
 *
 * @Auditable()
 */
class SFTPUser
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
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="SFTPUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="username", type="string", length=8, nullable=false)
     * @var string
     *
     * @Assert\Length(min=1, max=8)
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

    public function getHashedPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        if (!empty($password)) {
            $this->password = password_hash($password, \PASSWORD_ARGON2ID, [
                'memory_cost' => 65535,
                'time_cost' => 3,
                'threads' => 2,
            ]);
        }
    }

    public function getPublicKeys(): ?string
    {
        return $this->publicKeys;
    }

    public function getPublicKeysArray(): array
    {
        $pubKeysRaw = trim($this->publicKeys);
        if (!empty($pubKeysRaw)) {
            return explode("\n", $pubKeysRaw);
        }

        return [];
    }

    public function setPublicKeys(?string $publicKeys): void
    {
        $this->publicKeys = $publicKeys;
    }
}