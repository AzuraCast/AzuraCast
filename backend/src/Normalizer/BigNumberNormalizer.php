<?php

declare(strict_types=1);

namespace App\Normalizer;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Exception;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class BigNumberNormalizer implements DenormalizerInterface
{
    private const array SUPPORTED_TYPES = [
        BigInteger::class => true,
        BigDecimal::class => true,
        BigRational::class => true,
        BigNumber::class => true,
    ];

    public function getSupportedTypes(?string $format): array
    {
        return self::SUPPORTED_TYPES;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return isset(self::SUPPORTED_TYPES[$type]);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BigNumber
    {
        if (null === $data || '' === $data) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either an empty string or null, you should pass a string that '
                    . 'can be parsed as a BigNumber.',
                $data,
                ['string'],
                $context['deserialization_path'] ?? null,
                true
            );
        }
        try {
            /** @var class-string<BigNumber> $type */
            return $type::of($data);
        } catch (Exception $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $e->getMessage(),
                $data,
                ['string'],
                $context['deserialization_path'] ?? null,
                true,
                $e->getCode(),
                $e
            );
        }
    }
}
