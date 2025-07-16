<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Attributes\Auditable;
use App\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use OpenApi\Attributes as OA;
use phpseclib3\Crypt\PublicKeyLoader;
use Symfony\Component\Validator\Constraints as Assert;

use const PASSWORD_ARGON2ID;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'sftp_user'),
    ORM\UniqueConstraint(name: 'username_idx', columns: ['username']),
    UniqueEntity(fields: ['username']),
    Auditable
]
final class SftpUser implements
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;

    #[
        ORM\ManyToOne(inversedBy: 'sftp_users'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public readonly Station $station;

    #[
        OA\Property,
        ORM\Column(length: 32),
        Assert\Length(min: 1, max: 32),
        Assert\NotBlank,
        Assert\Regex(pattern: '/^[a-zA-Z0-9-_.~]+$/')
    ]
    public string $username;

    #[
        OA\Property,
        ORM\Column(length: 255),
        Assert\NotBlank
    ]
    public string $password {
        get => '';
        set {
            if (!empty($password)) {
                $this->password = password_hash($password, PASSWORD_ARGON2ID);
            }
        }
    }

    #[
        OA\Property,
        ORM\Column(name: 'public_keys', type: 'text', nullable: true)
    ]
    public ?string $publicKeys = null;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return string[]
     */
    public function getPublicKeysArray(): array
    {
        if (null === $this->publicKeys) {
            return [];
        }

        $pubKeysRaw = trim($this->publicKeys);
        if (!empty($pubKeysRaw)) {
            return array_filter(
                array_map([$this, 'cleanPublicKey'], explode("\n", $pubKeysRaw))
            );
        }

        return [];
    }

    public function authenticate(?string $password = null, ?string $pubKey = null): bool
    {
        if (!empty($password)) {
            return password_verify($password, $this->password);
        }

        if (!empty($pubKey)) {
            $pubKeys = $this->getPublicKeysArray();
            return in_array($this->cleanPublicKey($pubKey), $pubKeys, true);
        }

        return false;
    }

    private function cleanPublicKey(string $pubKeyRaw): ?string
    {
        try {
            $pkObj = PublicKeyLoader::loadPublicKey(trim($pubKeyRaw));
            return trim($pkObj->toString('OpenSSH', ['comment' => '']));
        } catch (Exception) {
            return null;
        }
    }
}
