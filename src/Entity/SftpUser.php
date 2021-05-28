<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog\Auditable;
use App\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

#[ORM\Entity, ORM\Table(name: 'sftp_user')]
#[ORM\UniqueConstraint(name: 'username_idx', columns: ['username'])]
#[UniqueEntity(fields: ['username'])]
#[Auditable]
class SftpUser
{
    #[ORM\Column(nullable: false)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id;

    #[ORM\ManyToOne(inversedBy: 'sftp_users')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(length: 32)]
    #[Assert\Length(min: 1, max: 32)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9-_.~]+$/')]
    protected string $username;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    protected string $password;

    #[ORM\Column(type: 'text')]
    protected ?string $publicKeys;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getId(): ?int
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
