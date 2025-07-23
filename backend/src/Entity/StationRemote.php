<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Api\ResolvableUrl;
use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\RemoteAdapters;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;
use App\Radio\Remote\AbstractRemote;
use App\Utilities;
use Doctrine\ORM\Mapping as ORM;
use Psr\Http\Message\UriInterface;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    ORM\Entity,
    ORM\Table(name: 'station_remotes'),
    Attributes\Auditable
]
final class StationRemote implements
    Stringable,
    Interfaces\StationAwareInterface,
    Interfaces\StationMountInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\ValidateMaxBitrate;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'relay_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?Relay $relay = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $relay_id = null;

    #[ORM\Column(length: 255, nullable: false)]
    public string $display_name = '' {
        get {
            if (!empty($this->display_name)) {
                return $this->display_name;
            }

            if ($this->enable_autodj) {
                $format = $this->autodj_format;
                if (null !== $format) {
                    return $format->formatBitrate($this->autodj_bitrate);
                }
            }

            return Utilities\Strings::truncateUrl($this->url);
        }
        set (string|null $value) => $this->truncateNullableString($value) ?? '';
    }

    #[ORM\Column]
    public bool $is_visible_on_public_pages = true;

    #[ORM\Column(type: 'string', length: 50, enumType: RemoteAdapters::class)]
    public RemoteAdapters $type;

    #[ORM\Column]
    public bool $enable_autodj = false;

    #[ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)]
    public ?StreamFormats $autodj_format = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    public ?int $autodj_bitrate = null;

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

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $custom_listen_url = null {
        set => $this->truncateNullableString($value);
    }

    #[ORM\Column(length: 255, nullable: false)]
    public string $url = '' {
        set {
            if (empty($value)) {
                $this->url = '';
            } else {
                $uri = Utilities\Urls::parseUserUrl(
                    $value,
                    'Remote Relay URL'
                );

                $this->url = $this->truncateString((string)$uri);
            }
        }
    }

    public function getUrlAsUri(): UriInterface
    {
        return Utilities\Urls::parseUserUrl(
            $this->url,
            'Remote Relay ' . $this->__toString() . ' URL'
        );
    }

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $mount = null {
        set => $this->truncateNullableString($value, 150);
    }

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $admin_password = null {
        set => $this->truncateNullableString($value, 100);
    }

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    public ?int $source_port = null {
        set(int|string|null $value) {
            $value = Utilities\Types::intOrNull($value);
            if (0 === $value) {
                $value = null;
            }

            $this->source_port = $value;
        }
    }

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $source_mount = null {
        set => $this->truncateNullableString($value, 150);
    }

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $source_username = null {
        set => $this->truncateNullableString($value, 100);
    }

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $source_password = null {
        set => $this->truncateNullableString($value, 100);
    }

    #[ORM\Column]
    public bool $is_public = false;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    public int $listeners_unique = 0;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    public int $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getEnableAutodj(): bool
    {
        return $this->enable_autodj;
    }

    public function getAutodjFormat(): ?StreamFormats
    {
        return $this->autodj_format;
    }

    public function getAutodjBitrate(): ?int
    {
        return $this->autodj_bitrate;
    }

    public function getAutodjUsername(): ?string
    {
        return $this->source_username;
    }

    public function getAutodjPassword(): ?string
    {
        $password = $this->source_password;

        if (RemoteAdapters::Shoutcast2 === $this->type) {
            $mount = $this->source_mount;
            if (empty($mount)) {
                $mount = $this->mount;
            }

            if (!empty($mount)) {
                $password .= ':#' . $mount;
            }
        }

        return $password;
    }

    public function getAutodjMount(): ?string
    {
        if (RemoteAdapters::Icecast !== $this->type) {
            return null;
        }

        $mount = $this->source_mount;
        if (!empty($mount)) {
            return $mount;
        }

        return $this->mount;
    }

    public function getAutodjHost(): string
    {
        return $this->getUrlAsUri()->getHost();
    }

    /*
     * StationMountInterface compliance methods
     */

    public function getAutodjPort(): ?int
    {
        return $this->source_port ?? $this->getUrlAsUri()->getPort();
    }

    public function getAutodjProtocol(): StreamProtocols
    {
        $urlScheme = $this->getUrlAsUri()->getScheme();

        return match ($this->getAutodjAdapterType()) {
            RemoteAdapters::Shoutcast1, RemoteAdapters::Shoutcast2 => StreamProtocols::Icy,
            default => ('https' === $urlScheme) ? StreamProtocols::Https : StreamProtocols::Http
        };
    }

    public function getAutodjAdapterType(): AdapterTypeInterface
    {
        return $this->type;
    }

    public function getIsPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * @return bool Whether this remote relay can be hand-edited.
     */
    public function isEditable(): bool
    {
        return (RemoteAdapters::AzuraRelay !== $this->type);
    }

    public function getIsShoutcast(): bool
    {
        return match ($this->getAutodjAdapterType()) {
            RemoteAdapters::Shoutcast1, RemoteAdapters::Shoutcast2 => true,
            default => false,
        };
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractRemote $adapter
     */
    public function api(
        AbstractRemote $adapter
    ): Api\NowPlaying\StationRemote {
        $response = new Api\NowPlaying\StationRemote();

        $response->id = $this->id;
        $response->name = $this->display_name;
        $response->url = new ResolvableUrl($adapter->getPublicUrl($this));

        $response->listeners = new Api\NowPlaying\Listeners(
            total: $this->listeners_total,
            unique: $this->listeners_unique
        );

        if ($this->enable_autodj || (RemoteAdapters::AzuraRelay === $this->type)) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = $this->autodj_format?->value;
        }

        return $response;
    }

    public function __toString(): string
    {
        return $this->station . ' Relay: ' . $this->display_name;
    }
}
