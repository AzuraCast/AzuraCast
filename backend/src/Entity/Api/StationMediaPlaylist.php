<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationMediaPlaylist',
    type: 'object'
)]
final class StationMediaPlaylist
{
    public function __construct(
        #[OA\Property(
            description: 'The playlist identifier.',
            example: 1
        )]
        public readonly int $id,
        #[OA\Property(readOnly: true)]
        public readonly string $name,
        #[OA\Property(readOnly: true)]
        public readonly string $short_name,
        #[OA\Property(readOnly: true)]
        public readonly ?string $folder = null,
        #[OA\Property(readOnly: true)]
        public int $count = 1
    ) {
    }

    /**
     * @param self[] $items
     * @return self[]
     */
    public static function aggregate(array $items): array
    {
        $playlists = [];
        foreach ($items as $item) {
            $key = $item->folder ? $item->id . '_' . $item->folder : $item->id;

            if (isset($playlists[$key])) {
                $playlists[$key]->count++;
            } else {
                $playlists[$key] = $item;
            }
        }

        return array_values($playlists);
    }
}
