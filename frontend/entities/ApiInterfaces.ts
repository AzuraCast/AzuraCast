/* eslint-disable */
/* tslint:disable */
// @ts-nocheck
/*
 * ---------------------------------------------------------------
 * ## THIS FILE WAS GENERATED VIA SWAGGER-TYPESCRIPT-API        ##
 * ##                                                           ##
 * ## AUTHOR: acacode                                           ##
 * ## SOURCE: https://github.com/acacode/swagger-typescript-api ##
 * ---------------------------------------------------------------
 */

export enum WebhookTypes {
  Generic = "generic",
  Email = "email",
  TuneIn = "tunein",
  RadioDe = "radiode",
  RadioReg = "radioreg",
  GetMeRadio = "getmeradio",
  Discord = "discord",
  Telegram = "telegram",
  GroupMe = "groupme",
  Mastodon = "mastodon",
  Bluesky = "bluesky",
  GoogleAnalyticsV4 = "google_analytics_v4",
  MatomoAnalytics = "matomo_analytics",
  Twitter = "twitter",
  GoogleAnalyticsV3 = "google_analytics",
}

export enum WebhookTriggers {
  SongChanged = "song_changed",
  SongChangedLive = "song_changed_live",
  ListenerGained = "listener_gained",
  ListenerLost = "listener_lost",
  LiveConnect = "live_connect",
  LiveDisconnect = "live_disconnect",
  StationOffline = "station_offline",
  StationOnline = "station_online",
}

export enum RemoteAdapters {
  Shoutcast1 = "shoutcast1",
  Shoutcast2 = "shoutcast2",
  Icecast = "icecast",
  AzuraRelay = "azurarelay",
}

export enum MasterMePresets {
  MusicGeneral = "music_general",
  SpeechGeneral = "speech_general",
  EbuR128 = "ebu_r128",
  ApplePodcasts = "apple_podcasts",
  YouTube = "youtube",
}

export enum FrontendAdapters {
  Icecast = "icecast",
  Shoutcast = "shoutcast2",
  Rsas = "rsas",
  Remote = "remote",
}

export enum BackendAdapters {
  Liquidsoap = "liquidsoap",
  None = "none",
}

export enum AudioProcessingMethods {
  None = "none",
  Liquidsoap = "nrj",
  MasterMe = "master_me",
  StereoTool = "stereo_tool",
}

export enum SupportedThemes {
  Browser = "browser",
  Light = "light",
  Dark = "dark",
}

export enum SupportedLocales {
  English = "en_US.UTF-8",
  Czech = "cs_CZ.UTF-8",
  Dutch = "nl_NL.UTF-8",
  French = "fr_FR.UTF-8",
  German = "de_DE.UTF-8",
  Greek = "el_GR.UTF-8",
  Italian = "it_IT.UTF-8",
  Japanese = "ja_JP.UTF-8",
  Korean = "ko_KR.UTF-8",
  Norwegian = "nb_NO.UTF-8",
  Polish = "pl_PL.UTF-8",
  Portuguese = "pt_PT.UTF-8",
  PortugueseBrazilian = "pt_BR.UTF-8",
  Russian = "ru_RU.UTF-8",
  SimplifiedChinese = "zh_CN.UTF-8",
  Spanish = "es_ES.UTF-8",
  Swedish = "sv_SE.UTF-8",
  Turkish = "tr_TR.UTF-8",
  Ukrainian = "uk_UA.UTF-8",
}

export enum StationPermissions {
  All = "administer all",
  View = "view station management",
  Reports = "view station reports",
  Logs = "view station logs",
  Profile = "manage station profile",
  Broadcasting = "manage station broadcasting",
  Streamers = "manage station streamers",
  MountPoints = "manage station mounts",
  RemoteRelays = "manage station remotes",
  Media = "manage station media",
  Automation = "manage station automation",
  WebHooks = "manage station web hooks",
  Podcasts = "manage station podcasts",
}

export enum ReleaseChannel {
  RollingRelease = "latest",
  Stable = "stable",
}

export enum GlobalPermissions {
  All = "administer all",
  View = "view administration",
  Logs = "view system logs",
  Settings = "administer settings",
  ApiKeys = "administer api keys",
  Stations = "administer stations",
  CustomFields = "administer custom fields",
  Backups = "administer backups",
  StorageLocations = "administer storage locations",
}

export enum FlashLevels {
  Success = "success",
  Warning = "warning",
  Error = "danger",
  Info = "info",
}

export enum StationBackendPerformanceModes {
  LessMemory = "less_memory",
  LessCpu = "less_cpu",
  Balanced = "balanced",
  Disabled = "disabled",
}

export enum PodcastSources {
  Manual = "manual",
  Playlist = "playlist",
}

export enum PlaylistTypes {
  Standard = "default",
  OncePerXSongs = "once_per_x_songs",
  OncePerXMinutes = "once_per_x_minutes",
  OncePerHour = "once_per_hour",
  Advanced = "custom",
}

export enum PlaylistSources {
  Songs = "songs",
  RemoteUrl = "remote_url",
}

export enum PlaylistRemoteTypes {
  Stream = "stream",
  Playlist = "playlist",
  Other = "other",
}

export enum PlaylistOrders {
  Random = "random",
  Shuffle = "shuffle",
  Sequential = "sequential",
}

export enum IpSources {
  Local = "local",
  XForwardedFor = "xff",
  Cloudflare = "cloudflare",
}

export enum FileTypes {
  Directory = "directory",
  Media = "media",
  CoverArt = "cover_art",
  UnprocessableFile = "unprocessable_file",
  Other = "other",
}

export enum AnalyticsLevel {
  All = "all",
  NoIp = "no_ip",
  None = "none",
}

export interface ApiAccountChangePassword {
  /** The current account password. */
  current_password: string;
  /** The new account password. */
  new_password: string;
}

export interface ApiAccountNewApiKey {
  /** The newly generated API key. */
  readonly key: string;
}

export interface ApiAccountTwoFactorStatus {
  /** The current two-factor status for this account. */
  readonly two_factor_enabled: boolean;
}

export interface ApiAdminAuditLog {
  id: number;
  /** @format date-time */
  timestamp: string;
  operation: 1 | 2 | 3;
  operation_text: string;
  class: string;
  identifier: string;
  target_class: string | null;
  target: string | null;
  user: string | null;
  changes: ApiAdminAuditLogChangeset[];
}

export interface ApiAdminAuditLogChangeset {
  field: string;
  from: string;
  to: string;
}

export type ApiAdminBackup = HasLinks & {
  path: string;
  basename: string;
  pathEncoded: string;
  timestamp: number;
  size: number;
  storageLocationId: number;
};

export interface ApiAdminDebugQueue {
  name:
    | "high_priority"
    | "normal_priority"
    | "low_priority"
    | "search_index"
    | "media"
    | "podcast_media";
  count: number;
  url: string;
}

export interface ApiAdminDebugStation {
  id: number;
  name: string;
  clearQueueUrl: string;
  getNextSongUrl: string;
  getNowPlayingUrl: string;
}

export interface ApiAdminDebugLogEntry {
  /** @format date-time */
  datetime: string;
  channel: string;
  level: 100 | 200 | 250 | 300 | 400 | 500 | 550 | 600;
  message: string;
  context: any[];
  extra: any[];
  formatted: any;
}

export interface ApiAdminDebugLogResult {
  logs: ApiAdminDebugLogEntry[];
}

export interface ApiAdminDebugSyncTask {
  task: string;
  pattern: string | null;
  time: number;
  nextRun: number;
  url: string;
}

export interface ApiAdminGeoLiteStatus {
  version: string | null;
  key: string | null;
}

export interface ApiAdminPermission {
  id: string;
  name: string;
}

export interface ApiAdminPermissions {
  global: ApiAdminPermission[];
  station: ApiAdminPermission[];
}

export interface ApiAdminRelay {
  /**
   * Station ID
   * @example 1
   */
  id?: number;
  /**
   * Station name
   * @example "AzuraTest Radio"
   */
  name?: string | null;
  /**
   * Station "short code", used for URL and folder paths
   * @example "azuratest_radio"
   */
  shortcode?: string | null;
  /**
   * Station description
   * @example "An AzuraCast station!"
   */
  description?: string | null;
  /**
   * Station homepage URL
   * @example "https://www.azuracast.com/"
   */
  url?: string | null;
  /**
   * The genre of the station
   * @example "Variety"
   */
  genre?: string | null;
  /**
   * Which broadcasting software (frontend) the station uses
   * @example "shoutcast2"
   */
  type?: string | null;
  /**
   * The port used by this station to serve its broadcasts.
   * @example 8000
   */
  port?: number | null;
  /**
   * The relay password for the frontend (if applicable).
   * @example "p4ssw0rd"
   */
  relay_pw?: string;
  /**
   * The administrator password for the frontend (if applicable).
   * @example "p4ssw0rd"
   */
  admin_pw?: string;
  mounts?: ApiNowPlayingStationMount[];
}

export type ApiAdminRole = HasLinks & {
  readonly id?: number;
  /** @example "Super Administrator" */
  name: string;
  permissions?: ApiAdminRolePermissions;
  /** Whether this role is the protected "Super Administrator" role. */
  readonly is_super_admin?: boolean;
};

export interface ApiAdminRolePermissions {
  global?: GlobalPermissions[];
  station?: ApiAdminRoleStationPermission[];
}

export interface ApiAdminRoleStationPermission {
  /** The station ID. */
  id: number;
  permissions: StationPermissions[];
}

export interface ApiAdminRsasStatus {
  version: string | null;
  hasLicense: boolean;
}

export interface ApiAdminServerStatsCpuStats {
  total: ApiAdminServerStatsCpuStatsSection;
  cores: ApiAdminServerStatsCpuStatsSection[];
  load: number[];
}

export interface ApiAdminServerStatsCpuStatsSection {
  name: string;
  usage: string;
  idle: string;
  io_wait: string;
  steal: string;
}

export interface ApiAdminServerStatsMemoryStats {
  total_bytes: string;
  total_readable: string;
  free_bytes: string;
  free_readable: string;
  buffers_bytes: string;
  buffers_readable: string;
  cached_bytes: string;
  cached_readable: string;
  sReclaimable_bytes: string;
  sReclaimable_readable: string;
  shmem_bytes: string;
  shmem_readable: string;
  used_bytes: string;
  used_readable: string;
}

export interface ApiAdminServerStatsNetworkInterfaceReceived {
  speed_bytes: string;
  speed_readable: string;
  packets: string;
  errs: string;
  drop: string;
  fifo: string;
  frame: string;
  compressed: string;
  multicast: string;
}

export interface ApiAdminServerStatsNetworkInterfaceStats {
  interface_name: string;
  received: ApiAdminServerStatsNetworkInterfaceReceived;
  transmitted: ApiAdminServerStatsNetworkInterfaceTransmitted;
}

export interface ApiAdminServerStatsNetworkInterfaceTransmitted {
  speed_bytes: string;
  speed_readable: string;
  packets: string;
  errs: string;
  drop: string;
  fifo: string;
  frame: string;
  carrier: string;
  compressed: string;
}

export interface ApiAdminServerStats {
  cpu: ApiAdminServerStatsCpuStats;
  memory: ApiAdminServerStatsMemoryStats;
  swap: ApiAdminServerStatsStorageStats;
  disk: ApiAdminServerStatsStorageStats;
  network: ApiAdminServerStatsNetworkInterfaceStats[];
}

export interface ApiAdminServerStatsStorageStats {
  total_bytes: string;
  total_readable: string;
  free_bytes: string;
  free_readable: string;
  used_bytes: string;
  used_readable: string;
}

export type ApiAdminServiceData = HasLinks & {
  name: string;
  description: string;
  running: boolean;
};

export interface ApiAdminShoutcastStatus {
  version: string | null;
}

export interface ApiAdminStereoToolStatus {
  version: string | null;
}

export type ApiAdminStorageLocation = HasLinks & {
  /** @example 1 */
  id?: number;
  /**
   * The type of storage location.
   * @example "station_media"
   */
  type?: string;
  /**
   * The storage adapter to use for this location.
   * @example "local"
   */
  adapter?: string;
  /**
   * The local path, if the local adapter is used, or path prefix for S3/remote adapters.
   * @example "/var/azuracast/stations/azuratest_radio/media"
   */
  path?: string | null;
  /**
   * The credential key for S3 adapters.
   * @example "your-key-here"
   */
  s3CredentialKey?: string | null;
  /**
   * The credential secret for S3 adapters.
   * @example "your-secret-here"
   */
  s3CredentialSecret?: string | null;
  /**
   * The region for S3 adapters.
   * @example "your-region"
   */
  s3Region?: string | null;
  /**
   * The API version for S3 adapters.
   * @example "latest"
   */
  s3Version?: string | null;
  /**
   * The S3 bucket name for S3 adapters.
   * @example "your-bucket-name"
   */
  s3Bucket?: string | null;
  /**
   * The optional custom S3 endpoint S3 adapters.
   * @example "https://your-region.digitaloceanspaces.com"
   */
  s3Endpoint?: string | null;
  /**
   * The optional Dropbox App Key.
   * @example ""
   */
  dropboxAppKey?: string | null;
  /**
   * The optional Dropbox App Secret.
   * @example ""
   */
  dropboxAppSecret?: string | null;
  /**
   * The optional Dropbox Auth Token.
   * @example ""
   */
  dropboxAuthToken?: string | null;
  /**
   * The host for SFTP adapters
   * @example "127.0.0.1"
   */
  sftpHost?: string | null;
  /**
   * The username for SFTP adapters
   * @example "root"
   */
  sftpUsername?: string | null;
  /**
   * The password for SFTP adapters
   * @example "abc123"
   */
  sftpPassword?: string | null;
  /**
   * The port for SFTP adapters
   * @example 20
   */
  sftpPort?: number | null;
  /** The private key for SFTP adapters */
  sftpPrivateKey?: string | null;
  /** The private key pass phrase for SFTP adapters */
  sftpPrivateKeyPassPhrase?: string | null;
  /** @example "50 GB" */
  storageQuota?: string | null;
  /** @example "120000" */
  storageQuotaBytes?: string | null;
  /** @example "1 GB" */
  storageUsed?: string | null;
  /** @example "60000" */
  storageUsedBytes?: string | null;
  /** @example "1 GB" */
  storageAvailable?: string | null;
  /** @example "120000" */
  storageAvailableBytes?: string | null;
  /** @example "75" */
  storageUsedPercent?: number | null;
  /** @example "true" */
  isFull?: boolean;
  /**
   * The URI associated with the storage location.
   * @example "/var/azuracast/www"
   */
  uri?: string;
  /** The stations using this storage location, if any. */
  stations?: string[] | null;
};

export interface ApiAdminUpdateDetails {
  /**
   * The stable-equivalent branch your installation currently appears to be on.
   * @example "0.20.3"
   */
  current_release?: string | null;
  /**
   * The current latest stable release of the software.
   * @example "0.20.4"
   */
  latest_release?: string | null;
  /** If you are on the Rolling Release, whether your installation needs to be updated. */
  needs_rolling_update?: boolean;
  /** Whether a newer stable release is available than the version you are currently using. */
  needs_release_update?: boolean;
  /** If you are on the Rolling Release, the number of updates released since your version. */
  rolling_updates_available?: number;
  /** Whether you can seamlessly move from the Rolling Release channel to Stable without issues. */
  can_switch_to_stable?: boolean;
}

export interface ApiBatchResult {
  success: boolean;
  errors: string[];
}

export type ApiDashboard = ApiNowPlaying & HasLinks;

export type ApiDetailedSongHistory = ApiNowPlayingSongHistory & {
  /**
   * Number of listeners when the song playback started.
   * @example 94
   */
  listeners_start?: number;
  /**
   * Number of listeners when song playback ended.
   * @example 105
   */
  listeners_end?: number;
  /**
   * The sum total change of listeners between the song's start and ending.
   * @example 11
   */
  delta_total?: number;
  /**
   * Whether the entry is visible on public playlists.
   * @example true
   */
  is_visible?: boolean;
};

export interface ApiError {
  /**
   * The numeric code of the error.
   * @example 500
   */
  code: number;
  /**
   * The programmatic class of error.
   * @example "NotLoggedInException"
   */
  type: string;
  /**
   * The text description of the error.
   * @example "Error description."
   */
  message: string;
  /**
   * The HTML-formatted text description of the error.
   * @example "<b>Error description.</b><br>Detailed error text."
   */
  formatted_message: string | null;
  /** Stack traces and other supplemental data. */
  extra_data: any[];
  /**
   * Used for API calls that expect an \Entity\Api\Status type response.
   * @example false
   */
  success: boolean;
}

export type ApiFileList = HasLinks & {
  path?: string;
  path_short?: string;
  text?: string;
  type?: FileTypes;
  timestamp?: number;
  size?: number | null;
  media?: ApiStationMedia | null;
  dir?: ApiFileListDir | null;
};

export interface ApiFileListDir {
  playlists?: any[];
}

export interface ApiFormOption {
  value: any;
  text: string;
  description?: string | null;
}

export interface ApiFormOptionGroup {
  options: ApiFormOption[];
  label: string;
}

export type ApiGenericForm = Record<string, any>;

export type ApiFormNestedOptions = (ApiFormOption | ApiFormOptionGroup)[];

export type ApiFormSimpleOptions = ApiFormOption[];

export type ApiGenericBatchResult = ApiBatchResult & {
  records: {
    /** @format int64 */
    id: number;
    title: string;
  }[];
};

/** A hash-map array represented as an object. */
export type HashMap = object;

export interface ApiListener {
  /**
   * The listener's IP address
   * @example "127.0.0.1"
   */
  ip?: string;
  /**
   * The listener's HTTP User-Agent
   * @example "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.86 Safari/537.36"
   */
  user_agent?: string;
  /**
   * A unique identifier for this listener/user agent (used for unique calculations).
   * @example ""
   */
  hash?: string;
  /**
   * Whether the user is connected to a local mount point or a remote one.
   * @example false
   */
  mount_is_local?: boolean;
  /**
   * The display name of the mount point.
   * @example "/radio.mp3"
   */
  mount_name?: string;
  /**
   * UNIX timestamp that the user first connected.
   * @example 1609480800
   */
  connected_on?: number;
  /**
   * UNIX timestamp that the user disconnected (or the latest timestamp if they are still connected).
   * @example 1609480800
   */
  connected_until?: number;
  /**
   * Number of seconds that the user has been connected.
   * @example 30
   */
  connected_time?: number;
  device?: ApiListenerDevice;
  location?: ApiListenerLocation;
}

export interface ApiListenerDevice {
  /**
   * If the listener device is likely a browser.
   * @example true
   */
  is_browser?: boolean;
  /**
   * If the listener device is likely a mobile device.
   * @example true
   */
  is_mobile?: boolean;
  /**
   * If the listener device is likely a crawler.
   * @example true
   */
  is_bot?: boolean;
  /**
   * Summary of the listener client.
   * @example "Firefox 121.0, Windows"
   */
  client?: string | null;
  /**
   * Summary of the listener browser family.
   * @example "Firefox"
   */
  browser_family?: string | null;
  /**
   * Summary of the listener OS family.
   * @example "Windows"
   */
  os_family?: string | null;
}

export interface ApiListenerLocation {
  /**
   * The approximate city of the listener.
   * @example "Austin"
   */
  city?: string | null;
  /**
   * The approximate region/state of the listener.
   * @example "Texas"
   */
  region?: string | null;
  /**
   * The approximate country of the listener.
   * @example "United States"
   */
  country?: string | null;
  /**
   * A description of the location.
   * @example "Austin, Texas, US"
   */
  description?: string;
  /**
   * Latitude.
   * @format float
   * @example "30.000000"
   */
  lat?: number | null;
  /**
   * Latitude.
   * @format float
   * @example "-97.000000"
   */
  lon?: number | null;
}

export interface ApiLogContents {
  readonly contents: string;
  /** Whether the log file has ended at this point or has additional data. */
  readonly eof: boolean;
  readonly position: number | null;
}

export type ApiLogType = HasLinks & {
  readonly key: string;
  readonly name: string;
  readonly path: string;
  readonly tail: boolean;
};

export interface ApiNotification {
  title: string;
  body: string;
  type: FlashLevels;
  actionLabel: string | null;
  actionUrl: string | null;
}

export type ApiNowPlayingCurrentSong = ApiNowPlayingSongHistory & {
  /**
   * Elapsed time of the song's playback since it started.
   * @example 25
   */
  elapsed: number;
  /**
   * Remaining time in the song, in seconds.
   * @example 155
   */
  remaining: number;
};

export interface ApiNowPlayingListeners {
  /**
   * Total non-unique current listeners
   * @example 20
   */
  total: number;
  /**
   * Total unique current listeners
   * @example 15
   */
  unique: number;
  /**
   * Total non-unique current listeners (Legacy field, may be retired in the future.)
   * @example 20
   */
  current: number;
}

export interface ApiNowPlayingLive {
  /**
   * Whether the stream is known to currently have a live DJ.
   * @example false
   */
  is_live: boolean;
  /**
   * The current active streamer/DJ, if one is available.
   * @example "DJ Jazzy Jeff"
   */
  streamer_name: string;
  /**
   * The start timestamp of the current broadcast, if one is available.
   * @example "1591548318"
   */
  broadcast_start: number | null;
  /**
   * URL to the streamer artwork (if available).
   * @example "https://picsum.photos/1200/1200"
   */
  art: string | null;
}

export interface ApiNowPlaying {
  station: ApiNowPlayingStation;
  listeners: ApiNowPlayingListeners;
  live: ApiNowPlayingLive;
  now_playing: ApiNowPlayingCurrentSong | null;
  playing_next: ApiNowPlayingStationQueue | null;
  song_history: ApiNowPlayingSongHistory[];
  /**
   * Whether the stream is currently online.
   * @example true
   */
  is_online: boolean;
  /** Debugging information about where the now playing data comes from. */
  cache: "hit" | "database" | "station" | null;
}

export interface ApiNowPlayingSongHistory {
  /** Song history unique identifier */
  sh_id: number;
  /**
   * UNIX timestamp when playback started.
   * @example 1609480800
   */
  played_at: number;
  /**
   * Duration of the song in seconds
   * @example 180
   */
  duration: number;
  /**
   * Indicates the playlist that the song was played from, if available, or empty string if not.
   * @example "Top 100"
   */
  playlist: string | null;
  /**
   * Indicates the current streamer that was connected, if available, or empty string if not.
   * @example "Test DJ"
   */
  streamer: string | null;
  /** Indicates whether the song is a listener request. */
  is_request: boolean;
  song: ApiSong;
}

export interface ApiNowPlayingStation {
  /**
   * Station ID
   * @example 1
   */
  id: number;
  /**
   * Station name
   * @example "AzuraTest Radio"
   */
  name: string;
  /**
   * Station "short code", used for URL and folder paths
   * @example "azuratest_radio"
   */
  shortcode: string;
  /**
   * Station description
   * @example "An AzuraCast station!"
   */
  description: string;
  /**
   * Which broadcasting software (frontend) the station uses
   * @example "shoutcast2"
   */
  frontend: string;
  /**
   * Which AutoDJ software (backend) the station uses
   * @example "liquidsoap"
   */
  backend: string;
  /**
   * The station's IANA time zone
   * @example "America/Chicago"
   */
  timezone: string;
  /**
   * The full URL to listen to the default mount of the station
   * @example "http://localhost:8000/radio.mp3"
   */
  listen_url: string;
  /**
   * The public URL of the station.
   * @example "https://example.com/"
   */
  url: string | null;
  /**
   * The public player URL for the station.
   * @example "https://example.com/public/example_station"
   */
  public_player_url: string;
  /**
   * The playlist download URL in PLS format.
   * @example "https://example.com/public/example_station/playlist.pls"
   */
  playlist_pls_url: string;
  /**
   * The playlist download URL in M3U format.
   * @example "https://example.com/public/example_station/playlist.m3u"
   */
  playlist_m3u_url: string;
  /**
   * If the station is public (i.e. should be shown in listings of all stations)
   * @example true
   */
  is_public: boolean;
  mounts: ApiNowPlayingStationMount[];
  remotes: ApiNowPlayingStationRemote[];
  /**
   * If the station has HLS streaming enabled.
   * @example true
   */
  hls_enabled: boolean;
  /**
   * If the HLS stream should be the default one for the station.
   * @example true
   */
  hls_is_default: boolean;
  /**
   * The full URL to listen to the HLS stream for the station.
   * @example "https://example.com/hls/azuratest_radio/live.m3u8"
   */
  hls_url: string | null;
  /**
   * HLS Listeners
   * @example 1
   */
  hls_listeners: number;
}

export type ApiNowPlayingStationMount = ApiNowPlayingStationRemote & {
  /**
   * The relative path that corresponds to this mount point
   * @example "/radio.mp3"
   */
  path: string;
  /**
   * If the mount is the default mount for the parent station
   * @example true
   */
  is_default: boolean;
};

export interface ApiNowPlayingStationQueue {
  /**
   * UNIX timestamp when the AutoDJ is expected to queue the song for playback.
   * @example 1609480800
   */
  cued_at: number;
  /**
   * UNIX timestamp when playback is expected to start.
   * @example 1609480800
   */
  played_at: number | null;
  /**
   * Duration of the song in seconds
   * @format float
   * @example 180
   */
  duration: number;
  /**
   * Indicates the playlist that the song was played from, if available, or empty string if not.
   * @example "Top 100"
   */
  playlist: string | null;
  /** Indicates whether the song is a listener request. */
  is_request: boolean;
  song: ApiSong;
}

export interface ApiNowPlayingStationRemote {
  /**
   * Mount/Remote ID number.
   * @example 1
   */
  id: number;
  /**
   * Mount point name/URL
   * @example "/radio.mp3"
   */
  name: string;
  /**
   * Full listening URL specific to this mount
   * @example "http://localhost:8000/radio.mp3"
   */
  url: string;
  /**
   * Bitrate (kbps) of the broadcasted audio (if known)
   * @example 128
   */
  bitrate: number | null;
  /**
   * Audio encoding format of broadcasted audio (if known)
   * @example "mp3"
   */
  format: string | null;
  listeners: ApiNowPlayingListeners;
}

export type ApiPodcast = HasLinks & {
  id?: string;
  storage_location_id?: number;
  source?: string;
  playlist_id?: number | null;
  playlist_auto_publish?: boolean;
  title?: string;
  link?: string | null;
  description?: string;
  description_short?: string;
  is_enabled?: boolean;
  /** An array containing podcast-specific branding configuration */
  branding_config?: any[];
  language?: string;
  language_name?: string;
  author?: string;
  email?: string;
  has_custom_art?: boolean;
  art?: string;
  art_updated_at?: number;
  is_published?: boolean;
  episodes?: number;
  categories?: ApiPodcastCategory[];
};

export type ApiPodcastBatchResult = ApiBatchResult & {
  episodes: {
    id: string;
    title: string;
  }[];
  records: ApiPodcastEpisode[] | null;
};

export interface ApiPodcastCategory {
  category?: string;
  text?: string;
  title?: string;
  subtitle?: string | null;
}

export type ApiPodcastEpisode = HasLinks & {
  id?: string;
  title?: string;
  link?: string | null;
  description?: string;
  description_short?: string;
  explicit?: boolean;
  season_number?: number | null;
  episode_number?: number | null;
  created_at?: number;
  publish_at?: number;
  is_published?: boolean;
  has_media?: boolean;
  playlist_media_id?: string | null;
  playlist_media?: ApiSong | null;
  media?: ApiPodcastMedia | null;
  has_custom_art?: boolean;
  art?: string | null;
  art_updated_at?: number;
};

export interface ApiPodcastMedia {
  id?: string | null;
  original_name?: string | null;
  /** @format float */
  length?: number;
  length_text?: string | null;
  path?: string | null;
}

export type ApiResolvableUrl = string;

export type ApiSong = ApiHasSongFields & {
  /**
   * The song's 32-character unique identifier hash
   * @example "9f33bbc912c19603e51be8e0987d076b"
   */
  id?: string;
  /**
   * URL to the album artwork (if available).
   * @example "https://picsum.photos/1200/1200"
   */
  art?: string;
  custom_fields?: string[];
};

export type ApiStationMedia = ApiHasSongFields &
  HasLinks & {
    /**
     * The media's identifier.
     * @example 1
     */
    id?: number;
    /**
     * A unique identifier associated with this record.
     * @example "69b536afc7ebbf16457b8645"
     */
    unique_id?: string;
    /**
     * The media file's 32-character unique song identifier hash
     * @example "9f33bbc912c19603e51be8e0987d076b"
     */
    song_id?: string;
    /**
     * URL to the album art.
     * @example "https://picsum.photos/1200/1200"
     */
    art?: string;
    /**
     * The relative path of the media file.
     * @example "test.mp3"
     */
    path?: string;
    /**
     * The UNIX timestamp when the database was last modified.
     * @example 1609480800
     */
    mtime?: number;
    /**
     * The UNIX timestamp when the item was first imported into the database.
     * @example 1609480800
     */
    uploaded_at?: number;
    /**
     * The latest time (UNIX timestamp) when album art was updated.
     * @example 1609480800
     */
    art_updated_at?: number;
    /**
     * The song duration in seconds.
     * @format float
     * @example 240
     */
    length?: number;
    /**
     * The formatted song duration (in mm:ss format)
     * @example "4:00"
     */
    length_text?: string;
    /** A hash-map array represented as an object. */
    custom_fields?: HashMap;
    /** A hash-map array represented as an object. */
    extra_metadata?: HashMap;
    playlists?: (ApiStationMediaPlaylist | number)[];
  };

export interface ApiStationMediaPlaylist {
  /**
   * The playlist identifier.
   * @example 1
   */
  id?: number;
  readonly name?: string;
  readonly short_name?: string;
  readonly count?: number;
}

export interface ApiStationOnDemand {
  /**
   * Track ID unique identifier
   * @example 1
   */
  track_id?: string;
  /**
   * URL to download/play track.
   * @example "/api/station/1/ondemand/download/1"
   */
  download_url?: string;
  media?: ApiSong;
  playlist?: string;
}

export interface ApiStationPlaylistQueue {
  /**
   * ID of the StationPlaylistMedia record associating this track with the playlist
   * @example 1
   */
  spm_id?: number | null;
  /**
   * ID of the StationPlaylistMedia record associating this track with the playlist
   * @example 1
   */
  media_id?: number;
  /**
   * The song's 32-character unique identifier hash
   * @example "9f33bbc912c19603e51be8e0987d076b"
   */
  song_id?: string;
  /**
   * The song artist.
   * @example "Chet Porter"
   */
  artist?: string;
  /**
   * The song title.
   * @example "Aluko River"
   */
  title?: string;
}

export interface ApiStationProfile {
  station: ApiNowPlayingStation;
  services: ApiStationServiceStatus;
  schedule: ApiStationSchedule[];
}

export type ApiStationQueueDetailed = ApiNowPlayingStationQueue &
  HasLinks & {
    /** Indicates whether the song has been sent to the AutoDJ. */
    sent_to_autodj?: boolean;
    /** Indicates whether the song has already been marked as played. */
    is_played?: boolean;
    /**
     * Custom AutoDJ playback URI, if it exists.
     * @example ""
     */
    autodj_custom_uri?: string | null;
    /** Log entries on how the specific queue item was picked by the AutoDJ. */
    log?: any[] | null;
  };

export interface ApiStationQuota {
  used?: string;
  used_bytes?: string;
  used_percent?: number;
  available?: string;
  available_bytes?: string;
  quota?: string | null;
  quota_bytes?: string | null;
  is_full?: boolean;
  num_files?: number | null;
}

export type ApiStationRemote = HasLinks & {
  id?: number | null;
  /** @example "128kbps MP3" */
  display_name?: string | null;
  /** @example true */
  is_visible_on_public_pages?: boolean;
  /** @example "icecast" */
  type?: string;
  /** @example "true" */
  is_editable?: boolean;
  /** @example false */
  enable_autodj?: boolean;
  /** @example "mp3" */
  autodj_format?: string | null;
  /** @example 128 */
  autodj_bitrate?: number | null;
  /** @example "https://custom-listen-url.example.com/stream.mp3" */
  custom_listen_url?: string | null;
  /** @example "https://custom-url.example.com" */
  url?: string;
  /** @example "/stream.mp3" */
  mount?: string | null;
  /** @example "password" */
  admin_password?: string | null;
  /** @example 8000 */
  source_port?: number | null;
  /** @example "/" */
  source_mount?: string | null;
  /** @example "source" */
  source_username?: string | null;
  /** @example "password" */
  source_password?: string | null;
  /** @example false */
  is_public?: boolean;
  /**
   * The most recent number of unique listeners.
   * @example 10
   */
  listeners_unique?: number;
  /**
   * The most recent number of total (non-unique) listeners.
   * @example 12
   */
  listeners_total?: number;
};

export interface ApiStationRequest {
  /**
   * Requestable ID unique identifier
   * @example 1
   */
  request_id?: string;
  /**
   * URL to directly submit request
   * @example "/api/station/1/request/1"
   */
  request_url?: string;
  song?: ApiSong;
}

export interface ApiStationRestartStatus {
  has_started?: boolean;
  needs_restart?: boolean;
}

export interface ApiStationSchedule {
  /**
   * Unique identifier for this schedule entry.
   * @example 1
   */
  id?: number;
  /**
   * The type of this schedule entry.
   * @example "playlist"
   */
  type?: "playlist" | "streamer";
  /**
   * Either the playlist or streamer's display name.
   * @example "Example Schedule Entry"
   */
  name?: string;
  /**
   * The name of the event.
   * @example "Example Schedule Entry"
   */
  title?: string;
  /**
   * The full name of the type and name combined.
   * @example "Playlist: Example Schedule Entry"
   */
  description?: string;
  /**
   * The start time of the schedule entry, in UNIX format.
   * @example 1609480800
   */
  start_timestamp?: number;
  /**
   * The start time of the schedule entry, in ISO 8601 format.
   * @example "020-02-19T03:00:00-06:00"
   */
  start?: string;
  /**
   * The end time of the schedule entry, in UNIX format.
   * @example 1609480800
   */
  end_timestamp?: number;
  /**
   * The start time of the schedule entry, in ISO 8601 format.
   * @example "020-02-19T05:00:00-06:00"
   */
  end?: string;
  /**
   * Whether the event is currently ongoing.
   * @example true
   */
  is_now?: boolean;
}

export interface ApiStationServiceStatus {
  /** @example true */
  backend_running: boolean;
  /** @example true */
  frontend_running: boolean;
  /** @example true */
  station_has_started: boolean;
  /** @example true */
  station_needs_restart: boolean;
}

export interface ApiStationStreamer {
  id: number;
  streamer_username: string;
  display_name: string;
}

export type ApiStationStreamerBroadcast = HasLinks & {
  id: number;
  /** @format date-time */
  timestampStart: string;
  /** @format date-time */
  timestampEnd: string | null;
  streamer: ApiStationStreamer | null;
  recording: ApiStationStreamerBroadcastRecording | null;
};

export interface ApiStationStreamerBroadcastRecording {
  path: string;
  size: number;
  downloadUrl: string;
}

export interface ApiStatus {
  /** @example true */
  success: boolean;
  /** @example "Changes saved successfully." */
  message: string;
  /** @example "<b>Changes saved successfully.</b>" */
  formatted_message: string;
}

export interface ApiSystemStatus {
  /**
   * Whether the service is online or not (should always be true)
   * @example true
   */
  online: boolean;
  /**
   * The current UNIX timestamp
   * @example 1609480800
   */
  timestamp: number;
}

export interface ApiTaskWithLog {
  /**
   * The URL to view logs of the ongoing background task.
   * @format uri
   */
  logUrl: string;
}

export interface ApiTime {
  /**
   * The current UNIX timestamp
   * @example 1497652397
   */
  timestamp: number;
  /** @example "2017-06-16 10:33:17" */
  utc_datetime: string;
  /** @example "June 16, 2017" */
  utc_date: string;
  /** @example "10:33pm" */
  utc_time: string;
  /** @example "2012-12-25T16:30:00.000000Z" */
  utc_json: string;
}

export interface HasLinks {
  readonly links?: Record<string, string>;
}

export interface ApiHasSongFields {
  /**
   * The song title, usually "Artist - Title"
   * @example "Chet Porter - Aluko River"
   */
  text?: string;
  /**
   * The song artist.
   * @example "Chet Porter"
   */
  artist?: string | null;
  /**
   * The song title.
   * @example "Aluko River"
   */
  title?: string | null;
  /**
   * The song album.
   * @example "Moving Castle"
   */
  album?: string | null;
  /**
   * The song genre.
   * @example "Rock"
   */
  genre?: string | null;
  /**
   * The International Standard Recording Code (ISRC) of the file.
   * @example "US28E1600021"
   */
  isrc?: string | null;
  /**
   * Lyrics to the song.
   * @example ""
   */
  lyrics?: string | null;
}

export interface ApiUploadFile {
  /**
   * The destination path of the uploaded file.
   * @example "relative/path/to/file.mp3"
   */
  path: string;
  /**
   * The base64-encoded contents of the file to upload.
   * @example ""
   */
  file: string;
}

export interface ApiUploadedRecordStatus {
  hasRecord: boolean;
  url: string | null;
}

export type ApiKey = HasSplitTokenFields & {
  user?: User;
  comment?: string;
};

export type CustomField = HasAutoIncrementId & {
  name: string;
  /** The programmatic name for the field. Can be auto-generated from the full name. */
  short_name?: string;
  /** An ID3v2 field to automatically assign to this value, if it exists in the media file. */
  auto_assign?: string | null;
};

export type Relay = HasAutoIncrementId & {
  /** @example "https://custom-url.example.com" */
  base_url?: string;
  /** @example "Relay" */
  name?: string | null;
  /** @example true */
  is_visible_on_public_pages?: boolean;
  /**
   * @format date-time
   * @example "2025-01-31T21:31:58+00:00"
   */
  created_at?: string;
  /**
   * @format date-time
   * @example "2025-01-31T21:31:58+00:00"
   */
  updated_at?: string;
};

export interface Settings {
  app_unique_identifier?: string;
  /**
   * Site Base URL
   * @example "https://your.azuracast.site"
   */
  base_url?: string | null;
  /**
   * AzuraCast Instance Name
   * @example "My AzuraCast Instance"
   */
  instance_name?: string | null;
  /**
   * Prefer Browser URL (If Available)
   * @example "false"
   */
  prefer_browser_url?: boolean;
  /**
   * Use Web Proxy for Radio
   * @example "false"
   */
  use_radio_proxy?: boolean;
  /** Days of Playback History to Keep */
  history_keep_days?: number;
  /**
   * Always Use HTTPS
   * @example "false"
   */
  always_use_ssl?: boolean;
  /**
   * API 'Access-Control-Allow-Origin' header
   * @example "*"
   */
  api_access_control?: string | null;
  /**
   * Whether to use high-performance static JSON for Now Playing data updates.
   * @example "false"
   */
  enable_static_nowplaying?: boolean;
  /** Listener Analytics Collection */
  analytics?: AnalyticsLevel | null;
  /**
   * Check for Updates and Announcements
   * @example "true"
   */
  check_for_updates?: boolean;
  /**
   * Results of the latest update check.
   * @example ""
   */
  update_results?: any[] | null;
  /**
   * The UNIX timestamp when updates were last checked.
   * @example 1609480800
   */
  update_last_run?: number;
  /**
   * Base Theme for Public Pages
   * @example "light"
   */
  public_theme?: SupportedThemes | null;
  /**
   * Hide Album Art on Public Pages
   * @example "false"
   */
  hide_album_art?: boolean;
  /**
   * Homepage Redirect URL
   * @example "https://example.com/"
   */
  homepage_redirect_url?: string | null;
  /**
   * Default Album Art URL
   * @example "https://example.com/image.jpg"
   */
  default_album_art_url?: string | null;
  /**
   * Attempt to fetch album art from external sources when processing media.
   * @example "false"
   */
  use_external_album_art_when_processing_media?: boolean;
  /**
   * Attempt to fetch album art from external sources in API requests.
   * @example "false"
   */
  use_external_album_art_in_apis?: boolean;
  /**
   * An API key to connect to Last.fm services, if provided.
   * @example "SAMPLE-API-KEY"
   */
  last_fm_api_key?: string | null;
  /**
   * Hide AzuraCast Branding on Public Pages
   * @example "false"
   */
  hide_product_name?: boolean;
  /**
   * Custom CSS for Public Pages
   * @example ""
   */
  public_custom_css?: string | null;
  /**
   * Custom JS for Public Pages
   * @example ""
   */
  public_custom_js?: string | null;
  /**
   * Custom CSS for Internal Pages
   * @example ""
   */
  internal_custom_css?: string | null;
  /**
   * Whether backup is enabled.
   * @example "false"
   */
  backup_enabled?: boolean;
  /**
   * The timecode (i.e. 400 for 4:00AM) when automated backups should run.
   * @example 400
   */
  backup_time_code?: string | null;
  /**
   * Whether to exclude media in automated backups.
   * @example "false"
   */
  backup_exclude_media?: boolean;
  /**
   * Number of backups to keep, or infinite if zero/null.
   * @example 2
   */
  backup_keep_copies?: number;
  /**
   * The storage location ID for automated backups.
   * @example 1
   */
  backup_storage_location?: number | null;
  /**
   * The output format for the automated backup.
   * @example "zip"
   */
  backup_format?: string | null;
  /**
   * The UNIX timestamp when automated backup was last run.
   * @example 1609480800
   */
  backup_last_run?: number;
  /**
   * The output of the latest automated backup task.
   * @example ""
   */
  backup_last_output?: string | null;
  /**
   * The UNIX timestamp when setup was last completed.
   * @example 1609480800
   */
  setup_complete_time?: number;
  /**
   * Temporarily disable all sync tasks.
   * @example "false"
   */
  sync_disabled?: boolean;
  /**
   * The last run timestamp for the unified sync task.
   * @example 1609480800
   */
  sync_last_run?: number;
  /**
   * This installation's external IP.
   * @example "192.168.1.1"
   */
  external_ip?: string | null;
  /**
   * The license key for the Maxmind Geolite download.
   * @example ""
   */
  geolite_license_key?: string | null;
  /**
   * The UNIX timestamp when the Maxmind Geolite was last downloaded.
   * @example 1609480800
   */
  geolite_last_run?: number;
  /**
   * Enable e-mail delivery across the application.
   * @example "true"
   */
  mail_enabled?: boolean;
  /**
   * The name of the sender of system e-mails.
   * @example "AzuraCast"
   */
  mail_sender_name?: string | null;
  /**
   * The e-mail address of the sender of system e-mails.
   * @example "example@example.com"
   */
  mail_sender_email?: string | null;
  /**
   * The host to send outbound SMTP mail.
   * @example "smtp.example.com"
   */
  mail_smtp_host?: string | null;
  /**
   * The port for sending outbound SMTP mail.
   * @example 465
   */
  mail_smtp_port?: number;
  /**
   * The username when connecting to SMTP mail.
   * @example "username"
   */
  mail_smtp_username?: string | null;
  /**
   * The password when connecting to SMTP mail.
   * @example "password"
   */
  mail_smtp_password?: string | null;
  /**
   * Whether to use a secure (TLS) connection when sending SMTP mail.
   * @example "true"
   */
  mail_smtp_secure?: boolean;
  /**
   * The external avatar service to use when fetching avatars.
   * @example "libravatar"
   */
  avatar_service?: string | null;
  /**
   * The default avatar URL.
   * @example ""
   */
  avatar_default_url?: string | null;
  /**
   * ACME (LetsEncrypt) e-mail address.
   * @example ""
   */
  acme_email?: string | null;
  /**
   * ACME (LetsEncrypt) domain name(s).
   * @example ""
   */
  acme_domains?: string | null;
  /** IP Address Source */
  ip_source?: IpSources | null;
}

export type SftpUser = HasAutoIncrementId & {
  username?: string;
  password?: string;
  publicKeys?: string | null;
};

export type Station = HasAutoIncrementId & {
  /**
   * The full display name of the station.
   * @example "AzuraTest Radio"
   */
  name?: string;
  /**
   * The URL-friendly name for the station, typically auto-generated from the full station name.
   * @example "azuratest_radio"
   */
  short_name?: string;
  /**
   * If set to 'false', prevents the station from broadcasting but leaves it in the database.
   * @example true
   */
  is_enabled?: boolean;
  frontend_type?: FrontendAdapters;
  /** An array containing station-specific frontend configuration */
  frontend_config?: object;
  backend_type?: BackendAdapters;
  /** An array containing station-specific backend configuration */
  backend_config?: object;
  /** @example "A sample radio station." */
  description?: string | null;
  /** @example "https://demo.azuracast.com/" */
  url?: string | null;
  /** @example "Various" */
  genre?: string | null;
  /** @example "/var/azuracast/stations/azuratest_radio" */
  radio_base_dir?: string | null;
  /**
   * Whether listeners can request songs to play on this station.
   * @example true
   */
  enable_requests?: boolean;
  /** @example 5 */
  request_delay?: number | null;
  /** @example 15 */
  request_threshold?: number | null;
  /** @example 0 */
  disconnect_deactivate_streamer?: number | null;
  /**
   * Whether streamers are allowed to broadcast to this station at all.
   * @example false
   */
  enable_streamers?: boolean;
  /**
   * Whether a streamer is currently active on the station.
   * @example false
   */
  is_streamer_live?: boolean;
  /**
   * Whether this station is visible as a public page and in a now-playing API response.
   * @example true
   */
  enable_public_page?: boolean;
  /**
   * Whether this station has a public 'on-demand' streaming and download page.
   * @example true
   */
  enable_on_demand?: boolean;
  /**
   * Whether the 'on-demand' page offers download capability.
   * @example true
   */
  enable_on_demand_download?: boolean;
  /**
   * Whether HLS streaming is enabled.
   * @example true
   */
  enable_hls?: boolean;
  /**
   * The number of 'last played' history items to show for a station in API responses.
   * @example 5
   */
  api_history_items?: number;
  /**
   * The time zone that station operations should take place in.
   * @example "UTC"
   */
  timezone?: string | null;
  /**
   * The maximum bitrate at which a station may broadcast, in Kbps. 0 for unlimited
   * @example 128
   */
  max_bitrate?: number;
  /**
   * The maximum number of mount points the station can have, 0 for unlimited
   * @example 3
   */
  max_mounts?: number;
  /**
   * The maximum number of HLS streams the station can have, 0 for unlimited
   * @example 3
   */
  max_hls_streams?: number;
  /** An array containing station-specific branding configuration */
  branding_config?: object;
};

export type StationHlsStream = HasAutoIncrementId & {
  /** @example "aac_lofi" */
  name?: string;
  /** @example "aac" */
  format?: any;
  /** @example 128 */
  bitrate?: number | null;
};

export type StationMount = HasAutoIncrementId & {
  /** @example "/radio.mp3" */
  name?: string;
  /** @example "128kbps MP3" */
  display_name?: string | null;
  /** @example true */
  is_visible_on_public_pages?: boolean;
  /** @example false */
  is_default?: boolean;
  /** @example false */
  is_public?: boolean;
  /** @example "/error.mp3" */
  fallback_mount?: string | null;
  /** @example "https://radio.example.com:8000/radio.mp3" */
  relay_url?: string | null;
  /** @example "" */
  authhash?: string | null;
  /** @example 43200 */
  max_listener_duration?: number;
  /** @example true */
  enable_autodj?: boolean;
  /** @example "mp3" */
  autodj_format?: any;
  /** @example 128 */
  autodj_bitrate?: number | null;
  /** @example "https://custom-listen-url.example.com/stream.mp3" */
  custom_listen_url?: string | null;
  frontend_config?: any[];
  /**
   * The most recent number of unique listeners.
   * @example 10
   */
  listeners_unique?: number;
  /**
   * The most recent number of total (non-unique) listeners.
   * @example 12
   */
  listeners_total?: number;
};

export type StationPlaylist = HasAutoIncrementId & {
  /** @example "Test Playlist" */
  name?: string;
  type?: PlaylistTypes;
  source?: PlaylistSources;
  order?: PlaylistOrders;
  /** @example "https://remote-url.example.com/stream.mp3" */
  remote_url?: string | null;
  /** @example "stream" */
  remote_type?: PlaylistRemoteTypes | null;
  /**
   * The total time (in seconds) that Liquidsoap should buffer remote URL streams.
   * @example 0
   */
  remote_buffer?: number;
  /** @example true */
  is_enabled?: boolean;
  /**
   * If yes, do not send jingle metadata to AutoDJ or trigger web hooks.
   * @example false
   */
  is_jingle?: boolean;
  /** @example 5 */
  play_per_songs?: number;
  /** @example 120 */
  play_per_minutes?: number;
  /** @example 15 */
  play_per_hour_minute?: number;
  /** @example 3 */
  weight?: number;
  /** @example true */
  include_in_requests?: boolean;
  /**
   * Whether this playlist's media is included in 'on demand' download/streaming if enabled.
   * @example true
   */
  include_in_on_demand?: boolean;
  /** @example "interrupt,loop_once,single_track,merge" */
  backend_options?: string | null;
  /** @example true */
  avoid_duplicates?: boolean;
  /** StationSchedule> */
  schedule_items?: any[];
  /** Podcast> */
  podcasts?: any[];
};

export type StationSchedule = HasAutoIncrementId & {
  /** @example 900 */
  start_time?: number;
  /** @example 2200 */
  end_time?: number;
  /**
   * Array of ISO-8601 days (1 for Monday, 7 for Sunday)
   * @example "0,1,2,3"
   */
  days?: string | null;
  /** @example false */
  loop_once?: boolean;
};

/** Station streamers (DJ accounts) allowed to broadcast to a station. */
export type StationStreamer = HasAutoIncrementId & {
  /** @example "dj_test" */
  streamer_username?: string;
  /** @example "" */
  streamer_password?: string;
  /** @example "Test DJ" */
  display_name?: string | null;
  /** @example "This is a test DJ account." */
  comments?: string | null;
  /** @example true */
  is_active?: boolean;
  /** @example false */
  enforce_schedule?: boolean;
  /** @example 1609480800 */
  reactivate_at?: number | null;
  /** StationSchedule> */
  schedule_items?: any[];
};

/** Each individual broadcast associated with a streamer. */
export type StationStreamerBroadcast = HasAutoIncrementId;

export type StationWebhook = HasAutoIncrementId & {
  /**
   * The nickname of the webhook connector.
   * @example "Twitter Post"
   */
  name?: string | null;
  type?: WebhookTypes;
  /** @example true */
  is_enabled?: boolean;
  /** List of events that should trigger the webhook notification. */
  triggers?: any[];
  /** Detailed webhook configuration (if applicable) */
  config?: any[];
  /** Internal details used by the webhook to preserve state. */
  metadata?: any[];
};

export interface HasAutoIncrementId {
  readonly id: number;
}

export interface HasSongFields {
  song_id?: string;
  text?: string | null;
  artist?: string | null;
  title?: string | null;
}

export interface HasSplitTokenFields {
  readonly id?: string;
  readonly verifier?: string;
}

export interface HasUniqueId {
  readonly id: string;
}

export type User = HasAutoIncrementId & {
  /** @example "demo@azuracast.com" */
  email?: string;
  /** @example "" */
  new_password?: string | null;
  /** @example "Demo Account" */
  name?: string | null;
  /** @example "en_US" */
  locale?: string | null;
  /** @example true */
  show_24_hour_time?: boolean | null;
  /** @example "A1B2C3D4" */
  two_factor_secret?: string | null;
  /** @example 1609480800 */
  created_at?: number;
  /** @example 1609480800 */
  updated_at?: number;
  /** Role> */
  roles?: any[];
};
