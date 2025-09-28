<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Api\ResolvableUrl;
use App\Radio\Backend\Liquidsoap\EncodableInterface;
use App\Radio\Backend\Liquidsoap\EncodingFormat;
use App\Radio\Backend\Liquidsoap\OutputtableInterface;
use App\Radio\Backend\Liquidsoap\OutputtableSource;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;
use App\Radio\Frontend\AbstractFrontend;
use App\Utilities\Urls;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_mounts'),
    Attributes\Auditable,
    ORM\HasLifecycleCallbacks
]
final class StationMount implements
    Stringable,
    OutputtableInterface,
    EncodableInterface,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\TruncateInts;
    use Traits\ValidateMaxBitrate;

    #[
        ORM\ManyToOne(inversedBy: 'mounts'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[
        OA\Property(example: "/radio.mp3"),
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    public string $name = '' {
        set => $this->truncateString('/' . ltrim($value, '/'), 100);
    }

    #[
        OA\Property(example: "128kbps MP3"),
        ORM\Column(length: 255, nullable: false)
    ]
    public string $display_name = '' {
        get {
            if (!empty($this->display_name)) {
                return $this->display_name;
            }

            if ($this->enable_autodj) {
                $format = $this->autodj_format;
                return (null !== $format)
                    ? $this->name . ' (' . $format->formatBitrate($this->autodj_bitrate) . ')'
                    : $this->name;
            }

            return $this->name;
        }
        set (string|null $value) => $this->truncateNullableString($value) ?? '';
    }

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $is_visible_on_public_pages = true;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    public bool $is_default = false;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    public bool $is_public = false;

    #[
        OA\Property(example: "/error.mp3"),
        ORM\Column(length: 100, nullable: true)
    ]
    public ?string $fallback_mount = null;

    #[
        OA\Property(example: "https://radio.example.com:8000/radio.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    public ?string $relay_url = null {
        set => $this->truncateNullableString($value);
    }

    public function getRelayUrlAsUri(): ?UriInterface
    {
        $relayUri = Urls::tryParseUserUrl(
            $this->relay_url,
            'Mount Point ' . $this->__toString() . ' Relay URL'
        );

        if (null !== $relayUri) {
            // Relays need port explicitly provided.
            $port = $relayUri->getPort();
            if ($port === null && '' !== $relayUri->getScheme()) {
                $relayUri = $relayUri->withPort(
                    ('https' === $relayUri->getScheme()) ? 443 : 80
                );
            }
        }

        return $relayUri;
    }

    #[
        OA\Property(example: ""),
        ORM\Column(length: 255, nullable: true)
    ]
    public ?string $authhash = null {
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(example: 43200),
        ORM\Column(type: 'integer', nullable: false)
    ]
    public int $max_listener_duration = 0;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    public bool $enable_autodj = true;

    #[
        OA\Property(example: "mp3"),
        ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)
    ]
    public ?StreamFormats $autodj_format = StreamFormats::Mp3;

    #[
        OA\Property(example: 128),
        ORM\Column(type: 'smallint', nullable: true)
    ]
    public ?int $autodj_bitrate = 128;

    #[Assert\Callback]
    public function hasValidBitrate(ExecutionContextInterface $context): void
    {
        $this->doValidateMaxBitrate(
            $context,
            $this->station->max_bitrate,
            $this->autodj_bitrate,
            'autodj_bitrate'
        );
    }

    #[
        OA\Property(example: "https://custom-listen-url.example.com/stream.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    public ?string $custom_listen_url = null {
        set => $this->truncateNullableString($value);
    }

    public function getCustomListenUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->custom_listen_url,
            'Mount Point ' . $this->__toString() . ' Listen URL'
        );
    }

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $intro_path = null;

    #[
        OA\Property,
        ORM\Column(type: 'text', nullable: true)
    ]
    public ?string $frontend_config = null;

    #[
        OA\Property(
            description: "The most recent number of unique listeners.",
            example: 10
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $listeners_unique = 0;

    #[
        OA\Property(
            description: "The most recent number of total (non-unique) listeners.",
            example: 12
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getEncodingFormat(): ?EncodingFormat
    {
        if (!$this->enable_autodj) {
            return null;
        }

        return new EncodingFormat(
            format: $this->autodj_format ?? StreamFormats::default(),
            bitrate: $this->autodj_bitrate ?? 128
        );
    }

    public function getOutputtableSource(): ?OutputtableSource
    {
        $encoding = $this->getEncodingFormat();
        if (null === $encoding) {
            return null;
        }

        $adapterType = $this->station->frontend_type;
        $frontendConfig = $this->station->frontend_config;

        return new OutputtableSource(
            encoding: $encoding,
            adapterType: $adapterType,
            host: '127.0.0.1',
            port: $frontendConfig->port,
            mount: $this->name,
            protocol: $adapterType === FrontendAdapters::Shoutcast
                ? StreamProtocols::Icy
                : null,
            username: '',
            password: $frontendConfig->source_pw,
            isPublic: $this->is_public
        );
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractFrontend $fa
     * @param UriInterface|null $baseUrl
     */
    public function api(
        AbstractFrontend $fa,
        ?UriInterface $baseUrl = null
    ): Api\NowPlaying\StationMount {
        $response = new Api\NowPlaying\StationMount();

        $response->id = $this->id;
        $response->name = $this->display_name;
        $response->path = $this->name;
        $response->is_default = $this->is_default;
        $response->url = new ResolvableUrl(
            $fa->getUrlForMount($this->station, $this, $baseUrl)
        );

        $response->listeners = new Api\NowPlaying\Listeners(
            total: $this->listeners_total,
            unique: $this->listeners_unique
        );

        if ($this->enable_autodj) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = $this->autodj_format?->value;
        }

        return $response;
    }

    public function __toString(): string
    {
        return $this->station . ' Mount: ' . $this->display_name;
    }
}
