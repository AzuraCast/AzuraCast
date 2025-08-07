<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Types;
use App\Utilities\Urls;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(schema: "StationBrandingConfiguration", type: "object")]
final class StationBrandingConfiguration extends AbstractArrayEntity
{
    #[OA\Property]
    public ?string $default_album_art_url = null {
        set => Types::stringOrNull($value, true);
    }

    public function getDefaultAlbumArtUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->default_album_art_url,
            'Station Default Album Art URL',
            false
        );
    }

    #[OA\Property]
    public ?string $public_custom_css = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $public_custom_js = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public ?string $offline_text = null {
        set => Types::stringOrNull($value, true);
    }
}
