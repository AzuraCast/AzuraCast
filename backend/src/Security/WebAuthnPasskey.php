<?php

declare(strict_types=1);

namespace App\Security;

use stdClass;

final class WebAuthnPasskey
{
    public function __construct(
        protected readonly string $id,
        protected readonly string $publicKeyPem
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHashedId(): string
    {
        return self::hashIdentifier($this->id);
    }

    public function getPublicKeyPem(): string
    {
        return $this->publicKeyPem;
    }

    public static function hashIdentifier(string $id): string
    {
        return hash('sha256', $id);
    }

    public static function fromWebAuthnObject(stdClass $data): self
    {
        return new self(
            $data->credentialId,
            $data->credentialPublicKey
        );
    }
}
