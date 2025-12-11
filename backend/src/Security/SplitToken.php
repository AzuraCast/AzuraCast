<?php

declare(strict_types=1);

namespace App\Security;

use InvalidArgumentException;
use SensitiveParameter;

final class SplitToken
{
    public const string SEPARATOR = ':';

    public string $identifier;

    public string $verifier;

    public function hashVerifier(): string
    {
        return hash('sha512', $this->verifier);
    }

    public function verify(
        #[SensitiveParameter] string $hashedVerifier
    ): bool {
        return hash_equals($hashedVerifier, $this->hashVerifier());
    }

    public function __toString(): string
    {
        return $this->identifier . self::SEPARATOR . $this->verifier;
    }

    public static function fromKeyString(
        #[SensitiveParameter] string $key
    ): self {
        $parts = explode(self::SEPARATOR, $key, 2);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Token is not in a valid format.');
        }

        [$identifier, $verifier] = $parts;

        if (empty($identifier) || empty($verifier)) {
            throw new InvalidArgumentException('Token is not in a valid format.');
        }

        $token = new self();
        $token->identifier = $identifier;
        $token->verifier = $verifier;

        return $token;
    }

    public static function isValidKeyString(
        #[SensitiveParameter] string $key
    ): bool {
        return str_contains($key, self::SEPARATOR);
    }

    public static function generate(): self
    {
        $randomStr = hash('sha256', random_bytes(32));

        $token = new self();
        $token->identifier = substr($randomStr, 0, 16);
        $token->verifier = substr($randomStr, 16, 32);

        return $token;
    }
}
