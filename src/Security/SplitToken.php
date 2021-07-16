<?php

declare(strict_types=1);

namespace App\Security;

use InvalidArgumentException;

class SplitToken
{
    public const SEPARATOR = ':';

    public string $identifier;

    public string $verifier;

    public function hashVerifier(): string
    {
        return hash('sha512', $this->verifier);
    }

    public function verify(string $hashedVerifier): bool
    {
        return hash_equals($hashedVerifier, $this->hashVerifier());
    }

    public function __toString(): string
    {
        return $this->identifier . self::SEPARATOR . $this->verifier;
    }

    public static function fromKeyString(string $key): self
    {
        [$identifier, $verifier] = explode(self::SEPARATOR, $key, 2);

        if (empty($identifier) || empty($verifier)) {
            throw new InvalidArgumentException('Token is not in a valid format.');
        }

        $token = new self();
        $token->identifier = $identifier;
        $token->verifier = $verifier;

        return $token;
    }

    public static function generate(): self
    {
        $random_str = hash('sha256', random_bytes(32));

        $token = new self();
        $token->identifier = substr($random_str, 0, 16);
        $token->verifier = substr($random_str, 16, 32);

        return $token;
    }
}
