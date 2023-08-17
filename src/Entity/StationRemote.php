<?php

declare(strict_types=1);

namespace App\Entity;

use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\RemoteAdapters;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;
use App\Radio\Remote\AbstractRemote;
use App\Utilities;
use Doctrine\ORM\Mapping as ORM;
use Psr\Http\Message\UriInterface;
use Stringable;

#[
    ORM\Entity,
    ORM\Table(name: 'station_remotes'),
    Attributes\Auditable
]
class StationRemote implements
    Stringable,
    Interfaces\StationMountInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'relay_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Relay $relay = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $relay_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $display_name = null;

    #[ORM\Column]
    protected bool $is_visible_on_public_pages = true;

    #[ORM\Column(type: 'string', length: 50, enumType: RemoteAdapters::class)]
    protected RemoteAdapters $type;

    #[ORM\Column]
    protected bool $enable_autodj = false;

    #[ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)]
    protected ?StreamFormats $autodj_format = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $autodj_bitrate = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $custom_listen_url = null;

    #[ORM\Column(length: 255, nullable: false)]
    protected string $url = '';

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $mount = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $admin_password = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    protected ?int $source_port = null;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $source_mount = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $source_username = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $source_password = null;

    #[ORM\Column]
    protected bool $is_public = false;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $listeners_unique = 0;

    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getRelay(): ?Relay
    {
        return $this->relay;
    }

    public function setRelay(?Relay $relay): void
    {
        $this->relay = $relay;
    }

    public function getIsVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $isVisibleOnPublicPages): void
    {
        $this->is_visible_on_public_pages = $isVisibleOnPublicPages;
    }

    public function getEnableAutodj(): bool
    {
        return $this->enable_autodj;
    }

    public function setEnableAutodj(bool $enableAutodj): void
    {
        $this->enable_autodj = $enableAutodj;
    }

    public function getAutodjFormat(): ?StreamFormats
    {
        return $this->autodj_format;
    }

    public function setAutodjFormat(?StreamFormats $autodjFormat = null): void
    {
        $this->autodj_format = $autodjFormat;
    }

    public function getAutodjBitrate(): ?int
    {
        return $this->autodj_bitrate;
    }

    public function setAutodjBitrate(int $autodjBitrate = null): void
    {
        $this->autodj_bitrate = $autodjBitrate;
    }

    public function getCustomListenUrl(): ?string
    {
        return $this->custom_listen_url;
    }

    public function setCustomListenUrl(?string $customListenUrl = null): void
    {
        $this->custom_listen_url = $this->truncateNullableString($customListenUrl);
    }

    public function getAutodjUsername(): ?string
    {
        return $this->getSourceUsername();
    }

    public function getSourceUsername(): ?string
    {
        return $this->source_username;
    }

    public function setSourceUsername(?string $sourceUsername): void
    {
        $this->source_username = $this->truncateNullableString($sourceUsername, 100);
    }

    public function getAutodjPassword(): ?string
    {
        $password = $this->getSourcePassword();

        if (RemoteAdapters::Shoutcast2 === $this->getType()) {
            $mount = $this->getSourceMount();
            if (empty($mount)) {
                $mount = $this->getMount();
            }

            if (!empty($mount)) {
                $password .= ':#' . $mount;
            }
        }

        return $password;
    }

    public function getSourcePassword(): ?string
    {
        return $this->source_password;
    }

    public function setSourcePassword(?string $sourcePassword): void
    {
        $this->source_password = $this->truncateNullableString($sourcePassword, 100);
    }

    public function getType(): RemoteAdapters
    {
        return $this->type;
    }

    public function setType(RemoteAdapters $type): void
    {
        $this->type = $type;
    }

    public function getSourceMount(): ?string
    {
        return $this->source_mount;
    }

    public function setSourceMount(?string $sourceMount): void
    {
        $this->source_mount = $this->truncateNullableString($sourceMount, 150);
    }

    public function getMount(): ?string
    {
        return $this->mount;
    }

    public function setMount(?string $mount): void
    {
        $this->mount = $this->truncateNullableString($mount, 150);
    }

    public function getAdminPassword(): ?string
    {
        return $this->admin_password;
    }

    public function setAdminPassword(?string $adminPassword): void
    {
        $this->admin_password = $adminPassword;
    }

    public function getAutodjMount(): ?string
    {
        if (RemoteAdapters::Icecast !== $this->getType()) {
            return null;
        }

        $mount = $this->getSourceMount();
        if (!empty($mount)) {
            return $mount;
        }

        return $this->getMount();
    }

    public function getAutodjHost(): ?string
    {
        return $this->getUrlAsUri()->getHost();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUrlAsUri(): UriInterface
    {
        return Utilities\Urls::parseUserUrl(
            $this->url,
            'Remote Relay ' . $this->__toString() . ' URL'
        );
    }

    public function setUrl(string $url): void
    {
        if (empty($url)) {
            $this->url = '';
        } else {
            $uri = Utilities\Urls::parseUserUrl(
                $url,
                'Remote Relay URL'
            );

            $this->url = $this->truncateString((string)$uri);
        }
    }

    /*
     * StationMountInterface compliance methods
     */

    public function getAutodjPort(): ?int
    {
        return $this->getSourcePort() ?? $this->getUrlAsUri()->getPort();
    }

    public function getSourcePort(): ?int
    {
        return $this->source_port;
    }

    public function setSourcePort(?int $sourcePort): void
    {
        if ((int)$sourcePort === 0) {
            $sourcePort = null;
        }

        $this->source_port = $sourcePort;
    }

    public function getAutodjProtocol(): ?StreamProtocols
    {
        $urlScheme = $this->getUrlAsUri()->getScheme();

        return match ($this->getAutodjAdapterType()) {
            RemoteAdapters::Shoutcast1, RemoteAdapters::Shoutcast2 => StreamProtocols::Icy,
            default => ('https' === $urlScheme) ? StreamProtocols::Https : StreamProtocols::Http
        };
    }

    public function getAutodjAdapterType(): AdapterTypeInterface
    {
        return $this->getType();
    }

    public function getIsPublic(): bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->is_public = $isPublic;
    }

    public function getListenersUnique(): int
    {
        return $this->listeners_unique;
    }

    public function setListenersUnique(int $listenersUnique): void
    {
        $this->listeners_unique = $listenersUnique;
    }

    public function getListenersTotal(): int
    {
        return $this->listeners_total;
    }

    public function setListenersTotal(int $listenersTotal): void
    {
        $this->listeners_total = $listenersTotal;
    }

    /**
     * @return bool Whether this remote relay can be hand-edited.
     */
    public function isEditable(): bool
    {
        return (RemoteAdapters::AzuraRelay !== $this->getType());
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

        $response->id = $this->getIdRequired();
        $response->name = $this->getDisplayName();
        $response->url = $adapter->getPublicUrl($this);

        $response->listeners = new Api\NowPlaying\Listeners(
            total: $this->listeners_total,
            unique: $this->listeners_unique
        );

        if ($this->enable_autodj || (RemoteAdapters::AzuraRelay === $this->getType())) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = $this->autodj_format?->value;
        }

        return $response;
    }

    public function getDisplayName(): string
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }

        if ($this->enable_autodj) {
            $format = $this->getAutodjFormat();
            if (null !== $format) {
                return $format->formatBitrate($this->autodj_bitrate);
            }
        }

        return Utilities\Strings::truncateUrl($this->url);
    }

    /**
     * @param string|null $displayName
     */
    public function setDisplayName(?string $displayName): void
    {
        $this->display_name = $this->truncateNullableString($displayName);
    }

    public function __toString(): string
    {
        return $this->getStation() . ' Relay: ' . $this->getDisplayName();
    }
}
