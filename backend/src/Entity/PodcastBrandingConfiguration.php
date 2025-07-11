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

    public const string ENABLE_OP3_PREFIX = 'enable_op3_prefix';

    public function getEnableOp3Prefix(): bool
    {
        return Types::bool($this->get(self::ENABLE_OP3_PREFIX), false, true);
    }

    public function setEnableOp3Prefix(string|bool $enable): void
    {
        $this->set(self::ENABLE_OP3_PREFIX, Types::bool($enable, false, true));
    }
}
