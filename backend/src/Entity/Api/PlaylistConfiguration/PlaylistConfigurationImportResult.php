<?php

declare(strict_types=1);

namespace App\Entity\Api\PlaylistConfiguration;

use App\Entity\Api\Status;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_PlaylistConfigurationImportResult',
    type: 'object'
)]
final readonly class PlaylistConfigurationImportResult extends Status
{
    #[OA\Property(example: 3)]
    public int $playlists_created;

    #[OA\Property(example: 10)]
    public int $media_relinked;

    #[OA\Property(example: 2)]
    public int $media_generated;

    #[OA\Property(example: 1)]
    public int $members_created;

    /** @var string[] */
    #[OA\Property(items: new OA\Items(type: 'string'))]
    public array $warnings;

    /**
     * @param string[] $warnings
     */
    public function __construct(
        bool $success = true,
        string $message = 'Changes saved successfully.',
        int $playlistsCreated = 0,
        int $mediaRelinked = 0,
        int $mediaGenerated = 0,
        int $membersCreated = 0,
        array $warnings = []
    ) {
        parent::__construct($success, $message);

        $this->playlists_created = $playlistsCreated;
        $this->media_relinked = $mediaRelinked;
        $this->media_generated = $mediaGenerated;
        $this->members_created = $membersCreated;
        $this->warnings = $warnings;
    }
}
