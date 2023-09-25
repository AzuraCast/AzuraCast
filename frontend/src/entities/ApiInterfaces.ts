/* eslint-disable */
/* tslint:disable */
/*
 * ---------------------------------------------------------------
 * ## THIS FILE WAS GENERATED VIA SWAGGER-TYPESCRIPT-API        ##
 * ##                                                           ##
 * ## AUTHOR: acacode                                           ##
 * ## SOURCE: https://github.com/acacode/swagger-typescript-api ##
 * ---------------------------------------------------------------
 */

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
  code?: number;
  /**
   * The programmatic class of error.
   * @example "NotLoggedInException"
   */
  type?: string;
  /**
   * The text description of the error.
   * @example "Error description."
   */
  message?: string;
  /**
   * The HTML-formatted text description of the error.
   * @example "<b>Error description.</b><br>Detailed error text."
   */
  formatted_message?: string | null;
  /** Stack traces and other supplemental data. */
  extra_data?: any[];
  /**
   * Used for API calls that expect an \Entity\Api\Status type response.
   * @example false
   */
  success?: boolean;
}

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
  /** Device metadata, if available */
  device?: any[];
  /** Location metadata, if available */
  location?: any[];
}

export type ApiNewRecord = ApiStatus & {
  links?: string[];
};

export type ApiNowPlayingCurrentSong = ApiNowPlayingSongHistory & {
  /**
   * Elapsed time of the song's playback since it started.
   * @example 25
   */
  elapsed?: number;
  /**
   * Remaining time in the song, in seconds.
   * @example 155
   */
  remaining?: number;
};

export interface ApiNowPlayingListeners {
  /**
   * Total non-unique current listeners
   * @example 20
   */
  total?: number;
  /**
   * Total unique current listeners
   * @example 15
   */
  unique?: number;
  /**
   * Total non-unique current listeners (Legacy field, may be retired in the future.)
   * @example 20
   */
  current?: number;
}

export interface ApiNowPlayingLive {
  /**
   * Whether the stream is known to currently have a live DJ.
   * @example false
   */
  is_live?: boolean;
  /**
   * The current active streamer/DJ, if one is available.
   * @example "DJ Jazzy Jeff"
   */
  streamer_name?: string;
  /**
   * The start timestamp of the current broadcast, if one is available.
   * @example "1591548318"
   */
  broadcast_start?: number | null;
  /**
   * URL to the streamer artwork (if available).
   * @example "https://picsum.photos/1200/1200"
   */
  art?: any;
}

export interface ApiNowPlaying {
  station?: ApiNowPlayingStation;
  listeners?: ApiNowPlayingListeners;
  live?: ApiNowPlayingLive;
  now_playing?: ApiNowPlayingCurrentSong | null;
  playing_next?: ApiNowPlayingStationQueue | null;
  song_history?: ApiNowPlayingSongHistory[];
  /**
   * Whether the stream is currently online.
   * @example true
   */
  is_online?: boolean;
  /** Debugging information about where the now playing data comes from. */
  cache?: "hit" | "database" | "station" | null;
}

export interface ApiNowPlayingSongHistory {
  /** Song history unique identifier */
  sh_id?: number;
  /**
   * UNIX timestamp when playback started.
   * @example 1609480800
   */
  played_at?: number;
  /**
   * Duration of the song in seconds
   * @example 180
   */
  duration?: number;
  /**
   * Indicates the playlist that the song was played from, if available, or empty string if not.
   * @example "Top 100"
   */
  playlist?: string | null;
  /**
   * Indicates the current streamer that was connected, if available, or empty string if not.
   * @example "Test DJ"
   */
  streamer?: string | null;
  /** Indicates whether the song is a listener request. */
  is_request?: boolean;
  song?: ApiSong;
}

export interface ApiNowPlayingStation {
  /**
   * Station ID
   * @example 1
   */
  id?: number;
  /**
   * Station name
   * @example "AzuraTest Radio"
   */
  name?: string;
  /**
   * Station "short code", used for URL and folder paths
   * @example "azuratest_radio"
   */
  shortcode?: string;
  /**
   * Station description
   * @example "An AzuraCast station!"
   */
  description?: string;
  /**
   * Which broadcasting software (frontend) the station uses
   * @example "shoutcast2"
   */
  frontend?: string;
  /**
   * Which AutoDJ software (backend) the station uses
   * @example "liquidsoap"
   */
  backend?: string;
  /**
   * The full URL to listen to the default mount of the station
   * @example "http://localhost:8000/radio.mp3"
   */
  listen_url?: any;
  /**
   * The public URL of the station.
   * @example "https://example.com/"
   */
  url?: string | null;
  /**
   * The public player URL for the station.
   * @example "https://example.com/public/example_station"
   */
  public_player_url?: any;
  /**
   * The playlist download URL in PLS format.
   * @example "https://example.com/public/example_station/playlist.pls"
   */
  playlist_pls_url?: any;
  /**
   * The playlist download URL in M3U format.
   * @example "https://example.com/public/example_station/playlist.m3u"
   */
  playlist_m3u_url?: any;
  /**
   * If the station is public (i.e. should be shown in listings of all stations)
   * @example true
   */
  is_public?: boolean;
  mounts?: ApiNowPlayingStationMount[];
  remotes?: ApiNowPlayingStationRemote[];
  /**
   * If the station has HLS streaming enabled.
   * @example true
   */
  hls_enabled?: boolean;
  /**
   * The full URL to listen to the HLS stream for the station.
   * @example "https://example.com/hls/azuratest_radio/live.m3u8"
   */
  hls_url?: any;
  /**
   * HLS Listeners
   * @example 1
   */
  hls_listeners?: number;
}

export type ApiNowPlayingStationMount = ApiNowPlayingStationRemote & {
  /**
   * The relative path that corresponds to this mount point
   * @example "/radio.mp3"
   */
  path?: string;
  /**
   * If the mount is the default mount for the parent station
   * @example true
   */
  is_default?: boolean;
};

export interface ApiNowPlayingStationQueue {
  /**
   * UNIX timestamp when the AutoDJ is expected to queue the song for playback.
   * @example 1609480800
   */
  cued_at?: number;
  /**
   * UNIX timestamp when playback is expected to start.
   * @example 1609480800
   */
  played_at?: number;
  /**
   * Duration of the song in seconds
   * @example 180
   */
  duration?: number;
  /**
   * Indicates the playlist that the song was played from, if available, or empty string if not.
   * @example "Top 100"
   */
  playlist?: string | null;
  /** Indicates whether the song is a listener request. */
  is_request?: boolean;
  song?: ApiSong;
}

export interface ApiNowPlayingStationRemote {
  /**
   * Mount/Remote ID number.
   * @example 1
   */
  id?: number;
  /**
   * Mount point name/URL
   * @example "/radio.mp3"
   */
  name?: string;
  /**
   * Full listening URL specific to this mount
   * @example "http://localhost:8000/radio.mp3"
   */
  url?: any;
  /**
   * Bitrate (kbps) of the broadcasted audio (if known)
   * @example 128
   */
  bitrate?: number | null;
  /**
   * Audio encoding format of broadcasted audio (if known)
   * @example "mp3"
   */
  format?: string | null;
  listeners?: ApiNowPlayingListeners;
}

export type ApiPodcast = HasLinks & {
  id?: string | null;
  storage_location_id?: number | null;
  title?: string | null;
  link?: string | null;
  description?: string | null;
  language?: string | null;
  author?: string | null;
  email?: string | null;
  has_custom_art?: boolean;
  art?: string | null;
  art_updated_at?: number;
  categories?: string[];
  episodes?: string[];
};

export type ApiPodcastEpisode = HasLinks & {
  id?: string | null;
  title?: string | null;
  description?: string | null;
  explicit?: boolean;
  publish_at?: number | null;
  has_media?: boolean;
  media?: ApiPodcastMedia;
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

export interface ApiSong {
  /**
   * The song's 32-character unique identifier hash
   * @example "9f33bbc912c19603e51be8e0987d076b"
   */
  id?: string;
  /**
   * The song title, usually "Artist - Title"
   * @example "Chet Porter - Aluko River"
   */
  text?: string;
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
  /**
   * The song album.
   * @example "Moving Castle"
   */
  album?: string;
  /**
   * The song genre.
   * @example "Rock"
   */
  genre?: string;
  /**
   * The International Standard Recording Code (ISRC) of the file.
   * @example "US28E1600021"
   */
  isrc?: string;
  /**
   * Lyrics to the song.
   * @example ""
   */
  lyrics?: string;
  /**
   * URL to the album artwork (if available).
   * @example "https://picsum.photos/1200/1200"
   */
  art?: any;
  custom_fields?: string[];
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
  backend_running?: boolean;
  /** @example true */
  frontend_running?: boolean;
  /** @example true */
  station_has_started?: boolean;
  /** @example true */
  station_needs_restart?: boolean;
}

export interface ApiStatus {
  /** @example true */
  success?: boolean;
  /** @example "Changes saved successfully." */
  message?: string;
  /** @example "<b>Changes saved successfully.</b>" */
  formatted_message?: string;
}

export interface ApiSystemStatus {
  /**
   * Whether the service is online or not (should always be true)
   * @example true
   */
  online?: boolean;
  /**
   * The current UNIX timestamp
   * @example 1609480800
   */
  timestamp?: number;
}

export interface ApiTime {
  /**
   * The current UNIX timestamp
   * @example 1497652397
   */
  timestamp?: number;
  /** @example "2017-06-16 10:33:17" */
  utc_datetime?: string;
  /** @example "June 16, 2017" */
  utc_date?: string;
  /** @example "10:33pm" */
  utc_time?: string;
  /** @example "2012-12-25T16:30:00.000000Z" */
  utc_json?: string;
}

export interface HasLinks {
  links?: string[];
}

export interface ApiUploadFile {
  /**
   * The destination path of the uploaded file.
   * @example "relative/path/to/file.mp3"
   */
  path?: string;
  /**
   * The base64-encoded contents of the file to upload.
   * @example ""
   */
  file?: string;
}

export type CustomField = HasAutoIncrementId & {
  name?: string;
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
  /** @example 1609480800 */
  created_at?: number;
  /** @example 1609480800 */
  updated_at?: number;
};

export type Role = HasAutoIncrementId & {
  /** @example "Super Administrator" */
  name?: string;
  /** RolePermission> */
  permissions?: any[];
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
  analytics?: any;
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
  public_theme?: any;
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
   * Whether to enable 'advanced' functionality in the system that is intended for power users.
   * @example false
   */
  enable_advanced_features?: boolean;
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
  ip_source?: any;
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
  /**
   * The frontend adapter (icecast,shoutcast,remote,etc)
   * @example "icecast"
   */
  frontend_type?: any;
  /** An array containing station-specific frontend configuration */
  frontend_config?: any[];
  /**
   * The backend adapter (liquidsoap,etc)
   * @example "liquidsoap"
   */
  backend_type?: any;
  /** An array containing station-specific backend configuration */
  backend_config?: any[];
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
  /** An array containing station-specific branding configuration */
  branding_config?: any[];
};

export type StationHlsStream = HasAutoIncrementId & {
  /** @example "aac_lofi" */
  name?: string;
  /** @example "aac" */
  format?: any;
  /** @example 128 */
  bitrate?: number | null;
};

export type StationMedia = HasAutoIncrementId &
  HasSongFields & {
    /**
     * A unique identifier associated with this record.
     * @example "69b536afc7ebbf16457b8645"
     */
    unique_id?: string | null;
    /**
     * The name of the media file's album.
     * @example "Test Album"
     */
    album?: string | null;
    /**
     * The genre of the media file.
     * @example "Rock"
     */
    genre?: string | null;
    /**
     * Full lyrics of the track, if available.
     * @example "...Never gonna give you up..."
     */
    lyrics?: string | null;
    /**
     * The track ISRC (International Standard Recording Code), used for licensing purposes.
     * @example "GBARL0600786"
     */
    isrc?: string | null;
    /**
     * The song duration in seconds.
     * @format float
     * @example 240
     */
    length?: number | null;
    /**
     * The formatted song duration (in mm:ss format)
     * @example "4:00"
     */
    length_text?: string | null;
    /**
     * The relative path of the media file.
     * @example "test.mp3"
     */
    path?: string;
    /**
     * The UNIX timestamp when the database was last modified.
     * @example 1609480800
     */
    mtime?: number | null;
    /**
     * The amount of amplification (in dB) to be applied to the radio source (liq_amplify)
     * @format float
     * @example -14
     */
    amplify?: number | null;
    /**
     * The length of time (in seconds) before the next song starts in the fade (liq_start_next)
     * @format float
     * @example 2
     */
    fade_overlap?: number | null;
    /**
     * The length of time (in seconds) to fade in the next track (liq_fade_in)
     * @format float
     * @example 3
     */
    fade_in?: number | null;
    /**
     * The length of time (in seconds) to fade out the previous track (liq_fade_out)
     * @format float
     * @example 3
     */
    fade_out?: number | null;
    /**
     * The length of time (in seconds) from the start of the track to start playing (liq_cue_in)
     * @format float
     * @example 30
     */
    cue_in?: number | null;
    /**
     * The length of time (in seconds) from the CUE-IN of the track to stop playing (liq_cue_out)
     * @format float
     * @example 30
     */
    cue_out?: number | null;
    /**
     * The latest time (UNIX timestamp) when album art was updated.
     * @example 1609480800
     */
    art_updated_at?: number;
    /** StationPlaylistMedia> */
    playlists?: any[];
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
  /** @example "default" */
  type?: any;
  /** @example "songs" */
  source?: any;
  /** @example "shuffle" */
  order?: any;
  /** @example "https://remote-url.example.com/stream.mp3" */
  remote_url?: string | null;
  /** @example "stream" */
  remote_type?: any;
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
  /**
   * The type of webhook connector to use.
   * @example "twitter"
   */
  type?: any;
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
  id?: number | null;
}

export interface HasSongFields {
  song_id?: string;
  text?: string | null;
  artist?: string | null;
  title?: string | null;
}

export interface HasUniqueId {
  id?: string | null;
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
