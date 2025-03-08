<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Debug;

use Monolog\LogRecord;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Debug_LogResult',
    required: ['*'],
    type: 'object'
)]
final readonly class LogResult
{
    public function __construct(
        #[OA\Property(
            items: new OA\Items(ref: LogEntry::class)
        )]
        public array $logs
    ) {
    }

    /**
     * @param LogRecord[] $logs
     * @return self
     */
    public static function fromTestHandlerRecords(array $logs): self
    {
        return new self(
            logs: array_map(
                fn(LogRecord $row) => LogEntry::fromLogRecord($row),
                $logs
            )
        );
    }
}
