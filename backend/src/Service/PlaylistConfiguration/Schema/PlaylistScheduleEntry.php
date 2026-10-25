<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Utilities\Types;
use JsonSerializable;

/**
 * @phpstan-type PlaylistScheduleShape array{
 *     start_time: int,
 *     end_time: int,
 *     days: int[],
 *     start_date: ?string,
 *     end_date: ?string,
 *     loop_once: bool,
 *     prevent_requests: bool,
 *     reset_queue_at_start: bool,
 *     reset_queue_recursive: bool
 * }
 */
final class PlaylistScheduleEntry implements JsonSerializable
{
    /**
     * @param int[] $days
     */
    public function __construct(
        public readonly int $startTime,
        public readonly int $endTime,
        public readonly array $days,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly bool $loopOnce,
        public readonly bool $preventRequests,
        public readonly bool $resetQueueAtStart = false,
        public readonly bool $resetQueueRecursive = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startTime: Types::int($data['start_time'] ?? null),
            endTime: Types::int($data['end_time'] ?? null),
            days: array_map('intval', Types::array($data['days'] ?? [])),
            startDate: Types::stringOrNull($data['start_date'] ?? null),
            endDate: Types::stringOrNull($data['end_date'] ?? null),
            loopOnce: Types::bool($data['loop_once'] ?? null),
            preventRequests: Types::bool($data['prevent_requests'] ?? null),
            resetQueueAtStart: Types::bool($data['reset_queue_at_start'] ?? null),
            resetQueueRecursive: Types::bool($data['reset_queue_recursive'] ?? null),
        );
    }

    /**
     * @return PlaylistScheduleShape
     */
    public function jsonSerialize(): mixed
    {
        return [
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'days' => $this->days,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'loop_once' => $this->loopOnce,
            'prevent_requests' => $this->preventRequests,
            'reset_queue_at_start' => $this->resetQueueAtStart,
            'reset_queue_recursive' => $this->resetQueueRecursive,
        ];
    }
}
