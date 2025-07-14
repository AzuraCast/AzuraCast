<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Types;
use App\Utilities\Urls;
use Psr\Http\Message\UriInterface;

class StationBrandingConfiguration extends AbstractArrayEntity
{
    public ?string $default_album_art_url {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public function getDefaultAlbumArtUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->default_album_art_url,
            'Station Default Album Art URL',
            false
        );
    }

    public ?string $public_custom_css {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $public_custom_js {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $offline_text {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }
}
