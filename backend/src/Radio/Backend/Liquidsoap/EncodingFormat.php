<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Radio\Enums\HlsStreamProfiles;
use App\Radio\Enums\StreamFormats;

final class EncodingFormat
{
    public function __construct(
        public StreamFormats $format,
        public int $bitrate,
        public ?HlsStreamProfiles $subProfile = null {
            get {
                if (null !== $this->subProfile) {
                    return $this->subProfile;
                }

                if (StreamFormats::Aac === $this->format) {
                    return ($this->bitrate >= 96)
                        ? HlsStreamProfiles::AacLowComplexity
                        : HlsStreamProfiles::AacHighEfficiencyV2;
                }

                return null;
            }
        }
    ) {
    }

    public function getVariableName(?string $prefix = null): string
    {
        return implode(
            '_',
            array_filter([
                $prefix,
                'enc',
                $this->subProfile?->getProfileName() ?? $this->format->value,
                $this->format !== StreamFormats::Flac ? $this->bitrate : null,
            ])
        );
    }
}
