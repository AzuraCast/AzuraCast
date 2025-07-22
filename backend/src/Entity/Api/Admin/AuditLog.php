<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\AuditLog as AuditLogEntity;
use App\Entity\Enums\AuditLogOperations;
use App\Utilities\Time;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_AuditLog',
    required: ['*'],
    type: 'object'
)]
final readonly class AuditLog
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $timestamp,
        #[OA\Property(enum: AuditLogOperations::class)]
        public int $operation,
        #[OA\Property]
        public string $operation_text,
        #[OA\Property]
        public string $class,
        #[OA\Property]
        public string $identifier,
        #[OA\Property]
        public ?string $target_class,
        #[OA\Property]
        public ?string $target,
        #[OA\Property]
        public ?string $user,
        #[OA\Property(
            items: new OA\Items(ref: AuditLogChangeset::class)
        )]
        public array $changes
    ) {
    }

    public static function fromRow(AuditLogEntity $row): self
    {
        $changesRaw = $row->changes;
        $changes = [];

        foreach ($changesRaw as $fieldName => [$fieldPrevious, $fieldNew]) {
            $changes[] = new AuditLogChangeset(
                $fieldName,
                json_encode(
                    $fieldPrevious,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                ),
                json_encode(
                    $fieldNew,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
        }

        $operation = $row->operation;

        return new self(
            $row->id,
            $row->timestamp->format(Time::JS_ISO8601_FORMAT),
            $operation->value,
            $operation->getName(),
            $row->class,
            $row->identifier,
            $row->targetClass,
            $row->target,
            $row->user,
            $changes
        );
    }
}
