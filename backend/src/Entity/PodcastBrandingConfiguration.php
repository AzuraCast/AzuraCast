<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Types;

class PodcastBrandingConfiguration extends AbstractStationConfiguration
{
    public const string PUBLIC_CUSTOM_HTML = 'public_custom_html';

    public function getPublicCustomHtml(): ?string
    {
        return Types::stringOrNull($this->get(self::PUBLIC_CUSTOM_HTML), true);
    }

    public function setPublicCustomHtml(?string $html): void
    {
        $this->set(self::PUBLIC_CUSTOM_HTML, $html);
    }
}
