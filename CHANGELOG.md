# Rolling Release Changes

These changes have not yet been incorporated into a stable release, but if you are on the latest version of the rolling
release channel, you can take advantage of these new features and fixes.

## New Features/Changes

## Code Quality/Technical Changes

## Bug Fixes

---

# AzuraCast 0.19.1 (Aug 21, 2023)

## New Features/Changes

- **Radio.de Webhook**: We have added a webhook allowing you to submit metadata changes to the popular German radio
  aggregator service [radio.de](https://radio.de).

## Code Quality/Technical Changes

- We replaced the "IANA Reserved Address" check for webhook URLs with a different method of restricting webhook debug
  log access to station operators. You can once again use "internal" IP addresses, like 192.168.x.x, as needed.

- All files and folders that begin with "." will be hidden from the station Media Manager panel.

- On data tables with more than 10 pages of data, you will now see a form input to enter any page number, and a "Go"
  button to go directly to that page. You can also hit the Enter key on your keyboard to jump to the entered page.

- Line numbers will appear next to certain log files when viewed (i.e. the Liquidsoap configuration), assisting in
  identifying which line of code is causing any reported errors.

## Bug Fixes

- If multiple values are provided for a given ID3 tag (i.e. a Genre tag separated by semicolons), we will include all of
  the genres in the processed file.

- Fixed an issue preventing the "Send Test E-mail" modal from appearing on the System Settings page.

- Fixed an issue preventing embeddable players from working in private (Incognito, Firefox/Safari Private) windows.

- Fixed an issue preventing advanced configuration, authhash and other settings from appearing on the Mount Points edit
  modal dialog.

- Fixed a minor UI bug affecting buttons and playback times in the header inline player.

- Fixed an issue causing the site background to not be transparent on dark theme embeds.

- Enabled searching on the Upcoming Song Queue page.

---

# AzuraCast 0.19.0 (Aug 17, 2023)

## New Features/Changes

- **Update to Liquidsoap 2.2.x**: We're updating to the latest version of Liquidsoap, which includes many bug fixes,
  performance improvements and other changes. We have adopted our syntax to match Liquidsoap's new supported syntax, but
  if you use custom Liquidsoap code, you will need to update your code accordingly. You can see the most important
  changes in this [migration guide](https://www.liquidsoap.info/doc-dev/migrating.html#from-2.1.x-to-2.2.x). The most
  common changes you will need to make are to mutable (`ref()`) variables:
    - `!var` becomes `var()`
    - `var := value` optionally becomes `var.set(value)`

- **Support for Direct StereoTool/Liquidsoap Integration**: In Liquidsoap 2.2.x, StereoTool is now directly supported
  within the software, resulting in better performance and significantly less delay in processing. We now support
  uploading the plugin version of StereoTool from the vendor's web site. We will use either the CLI version or the
  plugin version if it is uploaded. You can now also remove StereoTool from your installation entirely from the "Install
  StereoTool" page.

- **Initial Multi-Server Support**: AzuraCast now includes initial support for connecting multiple AzuraCast servers to
  a shared database instance. The system will automatically choose a "primary" instance that runs routine synchronized
  tasks, with a secondary instance that will automatically take over these functions if the main installation is
  nonresponsive.

- **Custom "Station Offline" and "Live Broadcast" Messages**: You can now specify, on a per-station level, the default
  message that shows when the station is offline or when a DJ has connected but has not yet sent any metadata. The
  former is located on the Branding Configuration page, and the latter is under the "Streamers/DJs" tab on the station
  profile.

## Code Quality/Technical Changes

- **Frontend Overhaul**: We have updated the code that powers the browser-facing frontend of our application. In
  particular, we've upgraded from Bootstrap 4 to 5. Most users of the application won't need to change anything as a
  result of this, but if you use custom CSS or JavaScript, the following changes will be necessary:
    - jQuery has been removed. If you used jQuery, you can likely replace any jQuery code with vanilla javascript. See
      migration guides like this one for help: https://tobiasahlin.com/blog/move-from-jquery-to-vanilla-javascript/
    - The theme selectors have changed from `[data-theme="dark/light"]` to `[data-bs-theme="dark/light"]`.
    - Several colors and parameters can be customized just by changing CSS variables. For more information on the
      specific CSS variables exposed by Bootstrap 5, visit this
      page: https://getbootstrap.com/docs/5.3/customize/css-variables/
    - Several class names and identifiers have been renamed. Use the Inspect Element tool to identify the new names.
    - The administration page and all per-station management panels are now "Single-Page Applications". Along with a
      smoother user experience on those sections, you'll also enjoy continued audio playback across pages!

## Bug Fixes

- When adding media to playlists, the system will no longer remove the media from all playlists and then re-add it; it
  will instead check the existing playlist associations and only add or remove modified ones. This will prevent issues
  with losing the playback order of media on sequential playlists and other related issues.

---

# AzuraCast 0.18.5 (Jun 16, 2023)

This release backports a bug fix from the current Rolling Release version so that users can take advantage of it
immediately:

- A bug preventing administrators from properly setting passwords for users via the Administer Users panel has been
  fixed.

---

# AzuraCast 0.18.3 (Jun 5, 2023)

This release solely exists to bump the version of AzuraCast up to 0.18.3, which was erroneously listed as the stable
release where the bug reports in 0.18.2 were fixed.

---

# AzuraCast 0.18.2 (Jun 5, 2023)

## New Features/Changes

## Code Quality/Technical Changes

- We have disabled the Meilisearch search tool, as it consumed a large amount of resources on smaller systems and it was
  difficult to ensure the index exactly matched the current state of the filesystem. We will be working to further
  optimize search queries to achieve similar improvements without any extra services.

- In sections of our application that depend on IP addresses, we've tightened our allowed IP addresses significantly to
  improve security and prevent brute-force flooding. If you're using a reverse proxy or CloudFlare, you should update
  your "IP Address Source" under the "System Settings" page.

## Bug Fixes

- File downloads will preserve the "Access-Control-Allowed-Origin" header you set in System Settings.

- Added extra reliability to fetching Now Playing information from remote relays.

- Charts won't attempt to load on the dashboard if analytics are disabled.

---

# AzuraCast 0.18.1 (Apr 21, 2023)

This is an incremental bug-fix release to apply fixes for an issue identified after the release of 0.18.0.

## New Features/Changes

- None

## Code Quality/Technical Changes

- Added improved HTML escaping at multiple points across the application. This is a precautionary measure in the wake of
  the CVE announced (and fixed in version 0.18.0). There are no known exploits targeting the sections of code remedied,
  but this helps ensure we are better insulated against such vulnerabilities moving forward.

## Bug Fixes

- Fixed a bug in newer builds of Icecast-KH that caused the service to fail to start up intermittently.

---

# AzuraCast 0.18.0 (Apr 19, 2023)

This release includes numerous important new features and a vulnerability fix that is particularly important for
multi-tenant installations (i.e. resellers). Upgrading is strongly recommended in these environments.

## New Features/Changes

- **Fix for [CVE-2023-2191](https://nvd.nist.gov/vuln/detail/CVE-2023-21910)**: An issue was identified where a user who
  already had an AzuraCast account could update their display name to inject malicious JavaScript into the header menu
  of the site. In a majority of cases, this menu is only visible to the current logged-in user (pages like the "
  Administer Users" page are unaffected by this vulnerability), but if a higher-privileged administrator uses the "Log
  In As" feature to masquerade as a user, then the JavaScript injection could exfiltrate certain data. Anonymous members
  of the public cannot exploit this vulnerability in an AzuraCast installation, so it is primarily of concern for
  multi-tenant installations (i.e. resellers).

- **Smarter, Faster Searches**: For searches in the Media Manager, as well as the public-facing Requests and On Demand
  pages, we now use a new search tool called Meilisearch that allows for very fast, very accurate search results, as
  well as more complex search queries (and other goodies, like typo correction).

- **Master_me and Post-Processing Tweaks**: We now have built-in support
  for [master_me](https://github.com/trummerschlunk/master_me), an open-source audio mastering tool that helps add
  polish and "punch" to your streams. Its functionality is similar to Stereo Tool, but because it's open-source, we
  include it in every AzuraCast installation. You can now also customize whether our post-processing step includes your
  live DJ performances.

## Code Quality/Technical Changes

- **Initial Podman Support**: Podman is an increasingly popular drop-in replacement for Docker, originally from the
  RedHat Enterprise Linux community of distributions. We have updated our Docker utility script to include a Podman
  support mode. Feel free to report any bugs to us!

- **Install Custom Packages at Startup**: If you want to take advantage of specific Ubuntu packages available
  via `apt-get install`, you can now specify those files in an `azuracast.env` environment variable
  named `INSTALL_PACKAGES_ON_STARTUP`. Because users can now install any extra packages they need, we are removing some
  non-essential packages from our shipped Docker image, namely several LADSPA audio plugins; to reinstall the full set
  of plugins that were previously available, add this line to your `azuracast.env` file:

  ```
  INSTALL_PACKAGES_ON_STARTUP="frei0r-plugins-dev multimedia-audio-plugins swh-plugins tap-plugins lsp-plugins-ladspa"
  ```

- Our Docker Utility Script now directly supports version 2 of Docker Compose (invoked using `docker compose` rather
  than `docker-compose`).

- Our Dropbox storage location support now includes support for Dropbox's new short-lived access tokens. We include
  instructions on how to set up and use Dropbox as an AzuraCast storage location on the Storage Locations administration
  page.

- We've re-tuned the Now Playing updates from how they worked in version 0.17.7 so they will no longer consume very high
  amounts of CPU and RAM on installations with many (30+) stations.

- We have made more changes to how our Message Queue system works in order to ensure we don't encounter a "runaway
  queue" problem with larger libraries.

- Icecast-KH has been updated to its latest version.

## Bug Fixes

- A minor bug causing timeouts with the Web Updater has been fixed.

- A bug causing stations to show as "Station Offline" immediately after an initial start or restart has been fixed.

- The error placeholder track (by default, "AzuraCast - AzuraCast is live!") will no longer show up in track history.

- A bug preventing station-specific branding changes from applying to embedded pages has been fixed.

---

# AzuraCast 0.17.7 (Jan 27, 2023)

## New Features/Changes

- **Web Updater**: We're rolling out an initial test of our web updater component for Docker installations. Based on
  [Watchtower](https://containrrr.dev/watchtower/), our updater sits outside our normal Docker image and can handle
  pulling the latest image for your installation, spinning down the current version and updating to the newer one, all
  from entirely within your web browser. Note that if you want to make any core configuration changes (i.e. change your
  release channel or other environment configuration) you must use the regular update script.

- **Per-Station Branding**: You can now provide custom album art, public page backgrounds, CSS and JavaScript on a
  per-station basis, using a new Station Branding page that is very similar to the system-wide Branding page.

- The newer version of Google Analytics (V4) is supported as a Web Hook option now.

## Code Quality/Technical Changes

- Redis was removed in version 0.17.6 in order to yield fewer running tasks on servers by default; we have noticed that,
  for some IO-limited servers, this imposes a significant performance penalty, so we have restored Redis to our default
  image. You can still disable it via the `ENABLE_REDIS` environment value.

- Under the hood, we have updated our frontend components to Vue 3, as Vue 2 will reach End of Life (EOL) in under a
  year. We are currently using a Vue 2 compatibility layer due to our UI library (BootstrapVue) only supporting Vue 2
  currently. Once this library updates, we will be able to fully use Vue 3, which will afford us significant performance
  improvements.

- On public player pages, we now emit a `player-ready` event that triggers when the Vue components are fully rendered;
  you can listen to this via `$(document).on('player-ready')` in custom JavaScript.

- The list of custom ID3 tags that can be assigned to Custom Fields has been expanded back to its original value.

## Bug Fixes

- The elapsed playback time on our public player pages is now based on the AzuraCast server time, ensuring that even if
  it's out of sync with your browser's time, this won't affect the elapsed play time. It's strongly recommended, if you
  can, to use the "High-Performance Now Playing Updates" system setting for the most accurate updates.

- Lossless (Flac) streams will no longer show a bitrate on Mount Points/Remote Relays.

- Podcast episodes will now properly be sorted by upload date in descending order, rather than name.

- If you change theme or language on the profile page, the page will reload to apply your changes.

- Sorting by custom fields is once again working on the Media Manager page.

---

# AzuraCast 0.17.6 (Dec 5, 2022)

## New Features/Changes

- **High-Performance Now Playing Updates are Back!** A few versions ago, we had to retire our previous
  high-performance (Websocket/SSE) Now Playing updates system due to an error in the library on Ubuntu 22.04. We have
  since found an excellent replacement library and implemented it. If you're using Websockets or Server-Sent Events (
  SSE) for your Now Playing updates, you'll need to make minor changes to how you connect, which we've documented
  here: https://docs.azuracast.com/en/developers/apis/now-playing-data#high-performance-updates

- On Mastodon and Twitter posts, you can now specify different message bodies for the different web hook trigger types (
  i.e. live DJ connect/disconnect or station online/offline).

- Web Hooks can now also be dispatched specifically when a song changes _and_ a DJ/streamer is live.

## Technical Changes

- When uploading a background or custom album art, whatever format is supplied (between PNG, JPG and WEBP) will be the
  format saved to disk. If possible, we recommend using WebP as it offers significant bandwidth savings.

## Bug Fixes

- Fixed a bug where posting URLs required "https://" prefixes but the documentation specifically said not to include
  them.

- Fixed an issue where malfunctioning stations would restart infinitely, causing excessive CPU load.

- Fixed a template issue preventing the service worker from working on public player pages.

---

# AzuraCast 0.17.5 (Nov 21, 2022)

## New Features/Changes

- **Mastodon Posting Support**: Publish to Mastodon via a Web Hook, the same way you do with Twitter!

- **Cover Art Files Support**: Many users keep the cover art for their media alongside the media in a separate image
  file. AzuraCast now detects image files in the same folder as your media and uses it as the default album art for that
  media. Because cover art files are often named a variety of things, we currently will use _any_ image file that exists
  alongside media. You can also now view cover art via the Media Manager UI.

- **24-Hour Time Display Support**: You can now choose whether to view time in 12 or 24 hour format from your user
  profile, or use the default settings for your locale.

## Code Quality/Technical Changes

- Because both our Docker and Ansible installations are managed by Supervisor now, we can view the realtime status of
  all essential application services, and even restart them directly from the web interface.

- If you enter the link for a public player page into a media player app (i.e. VLC), it will automatically redirect to
  the playlist file and play appropriately.

## Bug Fixes

- HLS streams will now be included in Playlist (PLS/M3U) file downloads.

- Fixed an issue where listener connection times over a day didn't properly show up.

- Fixed several issues contributing to slow load times on media manager pages.

- Fixed a bug where if a station only had "Allowed IPs", it wouldn't be enforced.

- Fixed changing the station's URL stub (short name) not prompting the user to reload the station configuration.

- Fixed an issue preventing the new Dropbox app key/app secret from saving.

---

# AzuraCast 0.17.4 (Oct 24, 2022)

## Code Quality/Technical Changes

- **Smarter database migrations:** A common source of problems with AzuraCast upgrades is experiencing a faulty or
  interrupted database migration, leaving your database in a state that we can't automatically recover from. While we
  can't wrap database changes in transactions due to our use of MariaDB, we can do the next best thing, which is to take
  an automatic snapshot of your database just prior to the migration and roll back to that automatically upon failure.
  This even applies if the entire update process is stopped and restarted, where the original database will be restored
  on the second update attempt.

- The enforcement of IP block rules has changed; now, if you have a list of "Allowed" IP addresses, this will be
  considered authoritative when evaluating whether a connecting listener is allowed; if the user is not on the allowed
  list, their connection will be rejected. If the "Allowed" list is empty, the previous normal rules (blocked IPs,
  countries, user agents, etc) will be followed instead.

- Storage locations using Dropbox can now use an App Key and App Secret to authenticate instead of auth tokens, which
  have been phased out in recent updates.

- In a previous version, we added a rule that would prevent stations from starting up unless they had at least one
  active playlist with at least one music file to play. Several stations reported unique edge cases that didn't work
  with this configuration, so it has been removed.

- Ansible installations now support Ubuntu 22.04 (Jammy) along with existing support for 20.04 (Focal).

## Bug Fixes

- Fixed a bug preventing the Remote Relays page from showing in some situations.

- Fixed a bug where sometimes, stations would create in an incomplete state with uninitialized storage locations, thus
  becoming impossible to manage or modify.

- Fixed an issue preventing station cloning from working.

- Jingles will no longer appear on the "best/worst performing songs" lists.

- Fixed a bug preventing Ogg (Vorbis, Opus, etc) files from being correctly processed.

- Fixed a bug where two stations that had a shared string in their short names (i.e. `station` and `station_1`) would
  conflict with one another, causing the public listening URLs of the latter station to fail to resolve correctly.

- Fixed a bug where directories in the "Move Files" modal weren't sorted by path.

- Fixed a situation where scheduled items could be queued to play before a DJ/streamer goes live, then be cued to play
  after they have finished, even if their scheduled time slot has ended during that broadcast.

- Fixed a bug preventing multiple playlists and/or custom fields from appearing in the bulk media CSV export.

- Fixed a bug causing song requests to use the interrupting queue instead of the regular song queue.

---

# AzuraCast 0.17.3 (Aug 3, 2022)

- Note: Development has slowed down while we continue to assist SilverEagle as he continues to find a new long term
  home.
  If you wish to assist SilverEagle during these times, please review this GitHub issue
    - [#5593](https://github.com/AzuraCast/AzuraCast/issues/5593)

## New Features/Changes

## Code Quality/Technical Changes

- In previous versions, "Jingle Mode" playlists wouldn't appear when displayed to users _or_ in the listener timeline
  reports. This makes it difficult to diagnose the full playback history of the station. The station playback timeline
  will now include jingle playlists, while they will remain hidden from public view.

## Bug Fixes

- Fixed a bug where the station could not be skipped if a Remote URL playlist was enabled.

- Fixed a bug with logging (Liquidsoap, Docker, etc) to reduce CPU load issues. 

- Fixed missing city fields in listener data.

- Fixed renewal checks & minor ACME issues for HTTPs (Thanks to skoerfgen)

---

# AzuraCast 0.17.2 (Jul 5, 2022)

## New Features/Changes

- **Full HLS Support**: HLS streams can now be listed in the public player (and preferred, if selected). HLS streams now
  also have full listener analytics support and will appear in station statistics and reports.

## Code Quality/Technical Changes

- You can now run `./docker.sh restore` with no arguments (i.e. exactly as specified here) to view a list of backups
  that are stored inside the Docker backups volume. This prevents you from needing to copy the backup file out of the
  Docker directory before restoring it.

- Several service logs are now available via the System Logs web UI even on Docker installations.

- SHOUTcast has been renamed to Shoutcast in all locations.

## Bug Fixes

- Fixed instances where "Copy to Clipboard" in a modal dialog box didn't actually copy to the clipboard.

- A minor bug causing the textareas on the "Edit Liquidsoap Configuration" page to erroneously scroll out of view when a
  textarea was being updated has been fixed.

- A bug causing a CPU overrun when trying to load Now Playing data for a station that had not started yet has been
  fixed.

- A bug causing stations to incorrectly be flagged as "Needs Restart" after routine updates has been resolved.

- A bug causing HLS streams to not properly disconnect when played via the web player has been resolved.

- If maximum listener duration is set, it will now properly be enforced by Shoutcast.

- The `/radio/8xx5` aliases for WebDJ connections have been re-added.

---

# AzuraCast 0.17.1 (Jun 16, 2022)

## New Features/Changes

- **Statistics Overhaul**: We've improved and expanded the reporting tools available to stations. The following reports
  are now available under a unified "Station Statistics" page; for each of these reports, you can specify a custom date
  range to narrow results:
    - Best/Worst Performing and Most Played Songs (All analytics levels)
    - Listeners by Day/Day of Week/Hour (All analytics levels)
    - Listeners by Total Listening Time (All analytics levels)
    - Listeners by Stream, i.e. Mount Point/Relay (All analytics levels)
    - Listeners by Client, i.e. Mobile/Desktop/Crawler/etc. (Full analytics only)
    - Listeners by Browser Family, i.e. Chrome/Firefox/etc. (Full analytics only)
    - Listeners by Country (Full analytics only)

- **LetsEncrypt via the Web**: We now support configuring LetsEncrypt via the web interface. If you had previously set
  up LetsEncrypt via the command line, your settings will be imported automatically. This update also adds LetsEncrypt
  support for Ansible installations. Note: If you are mounting a custom SSL certificate, the mounting locations have
  been updated to the following:
    - Full chain certificate: `/var/azuracast/acme/ssl.crt`
    - Private Key: `/var/azuracast/acme/ssl.key`

- When a live DJ disconnects, the AutoDJ will automatically skip to the next available track when resuming the regular
  broadcast.

## Code Quality/Technical Changes

- For stations using Liquidsoap, we now use the now-playing track information sent to us by Liquidsoap as the
  authoritative source of the currently playing track. This should remove a significant number of issues with
  Icecast/Shoutcast mangling song names and causing mismatches within our system. For non-Liquidsoap station operators,
  the currently playing song is still based on what is reported by Icecast/Shoutcast.

- Automated station playlist assignment (and the corresponding Song Performance Report) is being retired. Internally,
  this functionality was not well-explained, and likely does not work the way station operators expect it to. With the
  upcoming development of new, better reporting tools, this functionality will no longer be required.

## Bug Fixes

- Performance should be improved on several site components that previously were supposed to "lazy-load" their sub-items
  but did not properly do so.

- The incidence of "Malformed URI" exceptions should be greatly reduced, and if they occur the system will log what URL
  is causing the problem.

---

# AzuraCast 0.17.0 (Jun 6, 2022)

## New Features/Changes

- **HLS Support**: We now support the HTTP Live Streaming (HLS) format from directly within the AzuraCast web UI. Once
  enabled, you can configure the various bitrates and formats of your HLS stream the same way you would configure mount
  points; unlike mount points, however, your connecting listeners will automatically pick the one that suits their
  bandwidth the best. While this technology was originally developed for Apple devices, it has seen widespread adoption
  elsewhere. Note that because of how HLS is delivered, we cannot currently retrieve listener statistics for these
  streams.

- **Integrated Stereo Tool Support**: We now support the popular premium sound processing tool, Stereo Tool. Because the
  software is proprietary, you must first upload a copy of it via the System Administration page; you can then configure
  Stereo Tool on a per-station level, including uploading your own custom `.sts` configuration file.

- **Bulk Media CSV Import/Export**: You can now export all of your station's media and its associated metadata into a
  CSV file for editing in spreadsheet software of your choice. Once you've made your changes, upload the modified file
  from the same page and all of the changes will be applied in bulk, including basic metadata, associated playlists,
  cue/fade points, and custom fields.

- We have updated AzuraCast's AutoDJ scheduler to be able to handle the "Advanced" playlist configuration options
  itself, notably including the "Interrupt Other Tracks" setting. This means that enabling these settings will no longer
  force a playlist to use Liquidsoap for its scheduling.

- If the "Enforce Schedule" setting is enabled for a streamer and they overrun their scheduled time slot, the system
  will automatically disconnect the listener and prevent them from reconnecting for a time period (configurable via the
  station profile). THis can help prevent DJs from accidentally leaving their stream online and broadcasting "dead air".

- Streamers/DJs can have custom artwork uploaded for each streamer; during the streamer's broadcasts, if no other album
  art is available, the streamer's artwork will appear as the cover art instead.

- You can now customize the compression used for automatic backups.

## Code Quality/Technical Changes

- We can now write custom Nginx configuration on a per-station basis and automatically reload it on-the-fly without
  losing any active connections. This allows us to replace our standard `/radio/8000` web proxy URLs with
  station-specific `/listen/station_name` ones, among other improvements. If you are already using the
  older `/radio/8000`-style URLs, those will continue to work, and we have no plans to retire them in the near future.

- Since AzuraCast's services are all now accessible via `localhost`, several connections have been switched from TCP/IP
  to using Unix domain socket files. This not only reduces the number of used ports but improves performance.

- Internal services using ports from 9000-9010 have been moved to use other ports or sockets; while our default port
  allocation does not use these ports, many stations need to use ports in that range for legacy purposes, which should
  once again be possible.

- Docker users can now debug Slim Application Errors by editing the `SHOW_DETAILED_ERRORS` in the `azuracast.env` file,
  reports should be submitted to our [issues](https://github.com/azuracast/azuracast/issues) section for review by our
  team.

- SFTP support is now enabled for Ansible users as well.

## Bug Fixes

- Playlists powered by remote stream URLs will once again work as expected. Note that these playlist types _must_ be
  scheduled, as otherwise their indefinite duration will cause problems with radio operation.

- Remote URL playlists will now also support HLS (.m3u8) URLs.

- A bug preventing SFTP from properly supporting SSH public keys has been fixed.

- A minor security issue where SFTP would not properly disable if a station switched storage locations to a non-local
  one has been resolved.

- The library used to handle translations for the PHP side of the application has been switched, which should avoid many
  of the errors being seen by users not able to see translations in some sections of the site.

- When searching for items in searchable tables, the page will correctly reset to page 1.

- "Schedule View" now properly shows events that start on Saturday and roll over into Sunday.

---

# AzuraCast 0.16.1 (May 03, 2022)

## New Features/Changes

- **Play Immediately**: From the Media Manager, you can now trigger selected songs to play _immediately_, which will
  interrupt any existing songs that are currently playing and play the specified audio instead.

## Code Quality/Technical Changes

- The scheduler has been updated to follow a new rule for "Once per X Songs" playlists: it will only consider songs
  played from non-jingle playlists in its calculation. This will prevent other jingles from being counted in the total
  number of songs played in a time period.

## Bug Fixes

- A bug in Liquidsoap preventing "Skip Songs" from working properly has been fixed.

---

# AzuraCast 0.16.0 (Apr 27, 2022)

## New Features/Changes

- AzuraCast can now process new media files, including ScreamTracker Modules (.stm, .s3m), Module/Extended Modules
  (.mod, .xm), AIFF (.aiff), and Windows Media files (.wma, .wmv, .asf)

- Each station can now have its own custom "fallback" file (the error message that plays when you have no media
  configured or a broadcasting error otherwise occurs on your station) uploaded via the web UI.

- If a playlist is marked as requestable but has scheduled date/time limits, it will only be requestable within those
  scheduled dates/times.

- The System Administration homepage now includes much more detailed statistics on CPU and RAM consumption.

- You can now send a test e-mail to yourself from the same System Settings panel where you provide e-mail service info.

- A new report has been added, "Unassigned Files", that shows all media that has not been assigned to any playlist.

- A new advanced feature has been added that allows Liquidsoap users to broadly tune their installation to optimize in
  favor of using less CPU at the expense of memory, using less memory at the expense of CPU, or a "balanced"
  configuration between the two.

- Any IP ranges, countries or user agents you have banned from connecting to your stream will also be banned from
  submitting song requests to your station.

- For stations that support the zero-disconnect reload feature, you can now opt to either "Reload Configuration" (a soft
  reload that does not disconnect listeners) or "Restart Broadcasting" (a hard reload that does) in the event the latter
  is needed for troubleshooting.

- When editing custom Liquidsoap configuration, your changes will be evaluated immediately by Liquidsoap, which will
  alert you of any errors immediately, avoiding the need to restart broadcasting to test script changes.

- You can now specify a custom max timeout for "Generic" web hooks (that make HTTP requests to external URLs).

- You can now add Storage Locations that use SFTP connections.

## Code Quality/Technical Changes

- **Unified Docker Container**: We have combined all of our Docker containers into a single unified container that
  includes the database, cache, stations container and more. This combined container is located
  at [ghcr.io/azuracast/azuracast](https://github.com/azuracast/AzuraCast/pkgs/container/azuracast). For most users, no
  changes will be needed when migrating to the latest version of AzuraCast, but if you have created
  a `docker-compose.override.yml` file, you should
  follow [our instructions](https://github.com/AzuraCast/AzuraCast/issues/5191) to update the file.

- We have enabled the built-in "defender" service for our built-in SFTP provider, so repeated failed authentication
  attempts will automatically be blocked by the system. If you find yourself locked out of the system, restarting Docker
  will clear the block list.

- Stations that do not have any media assigned to any playlists won't start automatically, so for installations with
  many empty stations, resources will be significantly saved.

- The way Liquidsoap handles remote media locations (i.e. Dropbox or S3) has been rewritten to be more compatible and to
  work with features that previously didn't work, like advanced playlists. It will also automatically remove media when
  it's finished playing, which should help with disk space usage.

- All album art across the application has the `loading="lazy"` attribute to encourage supported browsers to defer its
  loading until after other content is rendered, improving performance.

- Internal API requests (Icecast listener auth, Liquidsoap API calls) are now handled via a separate, dedicated "back
  channel" and won't be affected as severely by heavy traffic on AzuraCast from public viewers or administrators.

- When processing media, we now use a combination of two libraries (php-getid3 and ffmpeg/ffprobe) to process media and
  retrieve rich metadata; this greatly expands the types of media that we can handle in AzuraCast to include essentially
  any media that Liquidsoap itself can play.

## Bug Fixes

- A bug preventing "Forgot Password" resets and login token generation from working was fixed.

- Deleting stations will no longer recursively delete their base directories. This is to prevent accidental deletion of
  media that is used by other services. If you want to clear the media from a station, you should remove it prior to
  deleting the station, or do so directly via the filesystem.

- Updating now automatically clears the unplayed station queue for all stations.

- A significant performance issue with the `station_queue` table has been identified and new indices have been added,
  resulting in significant improvements for some behind-the-scenes functionality (like AutoDJ "next song" calculation).

- A bug preventing playlists from being imported multiple times in the same pageview has been fixed.

- An issue with SSL auto-renewal not applying to Icecast direct port connections has been fixed.

- Websocket Now Playing updates now work on stations with non-ASCII characters in their "short name".

- Expanding text areas (i.e. in Edit Liquidsoap Configuration) will now properly expand to fit their contents.

---

# AzuraCast 0.15.2 (Feb 20, 2022)

## Bug Fixes

- Incorporated a bug fix version of Liquidsoap 2.0.3 that fixes issues with smart crossfading.

- Fixed a bug where some new installs could not continue due to an older version of Docker Compose being installed.

- Fixed a bug when reinstalling AzuraCast on top of an existing (or previous) installation.

---

# AzuraCast 0.15.1 (Feb 18, 2022)

## New Features/Changes

- **Zero-Downtime Broadcasting Restarts**: If you're using our default configuration (Liquidsoap as your AutoDJ and
  Icecast as your Broadcasting software), you no longer need to fear the "Restart to Apply Changes" button, as we've
  incorporated several soft-reload improvements that allow us to rebuild configuration files without disconnecting
  listeners. Both the "Restart to Apply Changes" and the "Restart System Broadcasting" link inside the 'Utilities'
  submenu will now soft-reload, which will not disconnect listeners on Icecast.

- **Blocking User Agents**: Station owners can now block specific user agents (or user-agent patterns, with wildcards)
  from connecting to their streams. This will prevent bots or malicious users from consuming excess bandwidth and
  appearing
  in system-wide reports.

## Code Quality/Technical Changes

- For Docker installations, we have removed our built-in multisite configuration in favor of a simpler default
  installation with fewer containers. If you are not using the multi-site setup (i.e. hosting another site on the same
  Docker installation), no changes are required to your installation. If you want to continue using the multi-site
  installation, you can follow
  the [instructions in our documentation](https://docs.azuracast.com/en/administration/docker/multi-site-installation).

- We have updated how we handle Listener Reports to significantly reduce both memory and overall processing times,
  meaning stations with large listener counts can now more easily view and export reports for long time periods.

- Updated to Liquidsoap version 2.0.3 on Ansible and Docker, this change includes some stability fixes and a patch for a
  memory leak within Liquidsoap version 2.0.2. We are still working on resolving some minor issues with it. Refer to our
  megathread for more information [#5017](https://github.com/AzuraCast/AzuraCast/issues/5017)

## Bug Fixes

- Fixed a bug where station base directories created with relative names would end up in `/var/azuracast/www/web`.

- Fixed an issue on Ansible installations preventing message queues from being processed correctly.

- Fixed a bug preventing Ansible installations or updates from completing successfully.

- Fixed a bug where album art on the song requests page wouldn't respect "Prefer Browser URL" setting.

- Fixed a bug where Liquidsoap wasn't calculating the ReplayGain values of tracks due to a missing binary.

- Added a missing Liquidsoap operator call to apply calculated ReplayGain values on the stream.

- Fixed an issue with backups failing to run and certain logs failing to view correctly.

---

# AzuraCast 0.15.0 (Jan 12, 2022)

## New Features/Changes

- **Docker ARM64 Images**: Thanks to advances in our build process and upstream software, we are now able to build our
  Docker images for both AMD64(X86_64) and ARM64 architectures. This means many devices that run 64-bit ARM
  architecture, like the Raspberry Pi 4 and other comparable devices, can now support the default installation method.
  We will continue to maintain the Ansible installation for the foreseeable future.

- **Liquidsoap 2.0**: This version introduces the latest version of the AutoDJ software we use, Liquidsoap 2.0. This
  version adds many new features, broad support for the powerful FFMpeg library, and more. If you haven't modified your
  AutoDJ configuration, you should not notice any impact from the new version; if you have custom code, you may need to
  migrate it to support the newer syntax. See
  the [Liquidsoap 2.0 migration guide](https://www.liquidsoap.info/doc-2.0.0/migrating.html) for more information.

- **Vue Components Everywhere**: As part of our Roadmap to 1.0, we've switched a vast majority of the AzuraCast
  application to be powered by Vue frontend components that connect directly to, and exclusively use, our powerful REST
  API to perform functions. Not only does this make for more snappy, responsive user experiences, but it also means
  that _everything_ you can do in the web application is now possible via the API as well; while we haven't documented
  all of these endpoints yet, you can use your browser's inspector console to see how we call our internal APIs and do
  the same in your own applications.

- The routine synchronization process has been completely rebuilt from the ground up to be concurrent and asynchronous:
    - The 1-minute, 5-minute and 1-hour sync tasks have been merged into a single task manager that staggers the tasks
      across the hour to ensure CPU load never has huge peaks at the top of the hour.
    - Each synchronized task is isolated in its own process, so any failure won't cause a failure for subsequent tasks.
    - The "Now Playing" synchronization is now isolated and runs per-station, so an outage on a single station won't
      affect other stations; this new worker-process method also ensures station metadata is checked more frequently.

- Storage Locations have been overhauled and made more useful:
    - Quotas are now enforced for all storage location types (media, recordings, podcasts, and backups)
    - Free space and used space is now shown on the podcast management page
    - Each storage location shows its space used and available in the storage location management page

- You can now clear the entire upcoming song queue with a single button click.

- If you are using the Shoutcast broadcasting software, you can input your user ID and license ID directly via the
  station profile.

## Code Quality/Technical Changes

- Translations are once again merged into a single file that is used by both frontend and backend, making things easier
  for our much-appreciated crew of localizers.

- Visual contrast has been improved in several areas for light theme users.

- As a result of the Vue migration, all components that use a file upload mechanism now have "chunked" upload support,
  which splits any uploads into smaller portions and avoids issues with maximum file size limits on the server-side.

- When the browser window is not focused, routine API calls will be slowed down significantly to avoid excess load on
  the server from inactive users; returning to the page will resume normal update speed.

- Several frontend libraries have been retired or exchanged for new versions as part of the Vue transition:
    - Replaced `moment.js` with `luxon`
    - Removed `jQuery Bootgrid`, `autosize`, `dirrty`

- Changes have also been made to backend software libraries as part of our development progress:
    - Removed and retired `AzuraForms` library
    - Added [doctrine-entity-normalizer](https://github.com/AzuraCast/doctrine-entity-normalizer)
    - Added [doctrine-batch-utilities](https://github.com/AzuraCast/doctrine-batch-utilities)

- Our Vue component build process has been completely overhauled to be fully independent of our legacy asset management;
  if you're contributing Vue components to our codebase, it should much more intuitively match the experience you would
  expect from other Vue-based apps using Webpack than before.

- Support for cloud-development environments Gitpod and Visual Studio Codespaces has been added, so you can jump
  directly into contributing without needing to set up a local development instance.

- XDebug is now built into the web image for developers to use; by default, it's disabled, but any tools (including the
  XDebug browser extensions) can enable it on-demand for specific requests.

## Bug Fixes

- Sorting now works properly with songs listed on the public request pages. (#4484)

- If a station is marked as disabled, its services will automatically be stopped and kept from restarting.

- The embedded "schedule" pages will now correctly show events throughout the week, not just for the next 48 hours.

- The LetsEncrypt container now remains present even if it originally wasn't configured to be present, which should fix
  some issues where LetsEncrypt setup doesn't work as expected for some users.

- The default maximum message size supported by Beanstalkd was too small for some of our API responses, particularly
  those with long fields (i.e. song lyrics); this has been fixed.

## Security Fixes

- Session identifiers will automatically regenerate when performing certain important actions (i.e. logging in or out).

- Session cookies are now marked as HTTP-only, avoiding possible use by custom JavaScript that may be injected into a
  given page.

- If the "Always Use HTTPS" setting is enabled, session cookies will be sent as "secure only" as well.

- API calls will now either require API key authentication _or_ both a current active login session and a unique
  identifier; if you're calling the API externally, you should _always_ use a generated API key and not count on the
  user's existing session.

- A minor cross-site scripting (XSS) vulnerability on public pages has been resolved.

---

# AzuraCast 0.14.1 (Aug 22, 2021)

## New Features/Changes

- If you're using the Icecast broadcasting software option, you can now block listeners from connecting from specified
  countries; this list can be maintained from the "Broadcasting" tab of the station profile. You can exempt specific IPs
  or IP ranges (using CIDR notation) from this block as well.

- We now support the self-hosted, free and open-source analytics tool Matomo for listener metrics; your Matomo
  installation can be added as a web hook and will receive listener data in periodic pings.

## Bug Fixes

- If your settings hide album art on public pages, this will also apply on requests and history dialogs/embeds too.

- Even for stations that only broadcast remotely, certain reports are now visible that weren't before.

- The "Worst" performing songs on the overview report will now properly show the worst performing songs.

- Numerous fixes have been made around strict typing, affecting the following areas:
    - Station API endpoints (Station ID can be a string and some non-numeric IDs are supported)
    - Various forms around the system that use numeric values
    - Setting schedule entries for playlists/streamers
    - CSV export functionality

- A minor issue with displaying rows on the Audit log has been fixed.

---

# AzuraCast 0.14.0 (Aug 6, 2021)

## New Features/Changes

- You can now directly upload a custom public page background, browser icon (favicon) and default album art.

- You can now duplicate a single playlist within a station, choosing whether to copy over the schedule entries or media
  associations that the current playlist has.

- Playlists can now be set to loop only once during their scheduled playback slot without needing to use Manual AutoDJ
  mode.

- You can now embed the "Schedule" panel from the station's profile into your own web page as an embeddabl component.

- Mount point updates:
    - You can now upload an introduction file that will be played to listeners when they initially connect. This file
      must match the bitrate and format of the stream itself, and is thus uploaded on a per-mount-point basis.
    - You can now broadcast in Ogg FLAC format.
    - You can now specify a maximum connected time in seconds, after which listeners are automatically disconnected.

- Podcast episodes now take advantage of the same multi-part uploader that our Media Manager already uses, making
  uploading larger files simple and avoiding file size issues.

## Code Quality/Technical Changes

- If you use non-standard radio ports on the Docker installation, nginx will automatically be configured to listen to
  those new ports for its web proxy instead of the default port range.

- The entire AzuraCast codebase has been set to "strict mode" in PHP, which will strictly enforce type safety across the
  application. To complement this, we have made hundreds of fixes across the application to ensure it passes the
  strictest "static analysis" standards available currently. This may result in new `TypeError` type exceptions being
  thrown in various files; please report those to us here via GitHub, and we will resolve them ASAP.

- Docker installation now includes a new, interactive installer that will dynamically generate a `docker-compose.yml`
  file optimized for your setup. This new installer is also localized, so more of the installation process will be
  available in your preferred language.

- We have once again switched our message queue implementation, this time from the MariaDB database to a
  super-lightweight standalone tool called Beanstalkd. We hope this will resolve issues we've encountered with parallel
  workers causing database lockups and other problems.

- Several installations with larger music collections were discovering that their media processing was being "held up"
  by a single file that caused the PHP metadata processor to run out of memory. We've isolated this code so it runs in
  its own standalone process, which should reduce the overall incidence of unrecoverable errors when processing media.

- The main web Docker container will now automatically initialize itself upon startup, performing essential tasks like
  updating the database, clearing the cache and ensuring the system is set up properly. This means even if you miss a
  step in installation (or use the Docker images directly) they should still work without issue.

- You can optionally disable Redis entirely, instead relying on flatfile caches for session management and other Redis
  functions by setting `ENABLE_REDIS=false` in `azuracast.env`. This is not recommended for most users as Redis offers
  great performance, but if you are looking to minimize the number of running containers, this is a viable option.

- One of the biggest issues with Docker file mounting has been permissions; you can now set a custom UID/GID for the
  running user inside the Docker containers, to match the one you use in your host operating system. To use this,
  set `AZURACAST_PUID` and `AZURACAST_PGID` in `.env` accordingly; both default to 1000.

- All up-to-date AzuraCast installations will opt users out of Google's new advertisement tracking system, FLoC. Learn
  more about this and why we disabled it [here](https://www.eff.org/deeplinks/2021/03/googles-floc-terrible-idea).

## Bug Fixes

- Remote relays to legacy Shoutcast 1 installations should once again work as expected (#4408).

- An issue causing localized date/time formats to not appear on some station management pages was fixed (#4394).

- Fixed a bug where files that included certain special non-ASCII characters would never be read or processed.

- We've added some extra information on how to enable ShoutCast DNAS Premium features in the `Edit Profile` panel
  under `Broadcasting`.

---

# AzuraCast 0.13.0 (Jun 15, 2021)

## New Features/Changes

- **Podcast Management (Beta):** You can now upload and manage podcasts directly via the AzuraCast web interface. Via
  this interface, you can create and manage individual podcast episodes and associate them with uploaded media (which
  can be managed in an interface similar to the Media Manager). Podcasts have their own automatically generated public
  pages and RSS feeds that are compatible with many major podcast aggregation services.

- **Automatic Theme Selection:** If you haven't set a default theme for either your user account or the AzuraCast public
  pages, the theme will automatically be determined by the user's browser based on their OS's theme preference (dark or
  light). You can override this by selecting a default theme in the "Branding" settings, or reset to using browser
  preference by selecting the "Prefer System Default" option.

- The built-in public players are now Progressive Web Apps (PWAs) that can be "installed" on browsers and mobile
  devices.

## Code Quality/Technical Changes

- Several of our upstream dependencies (Doctrine ORM, Symfony Serializer and Validator) have updated to support PHP
  8.0's attributes, and we have updated our code to reflect those changes.

- The embeddable data on each station's public player is now "OpenGraph" compatible, which should work across multiple
  social media platforms, including the existing Twitter player implementation.

- Unauthenticated users viewing paginated data (i.e. requestable tracks) are limited to viewing 25 rows per page to
  avoid excessive server load.

- When streaming to a Remote Relay with a URL beginning in "https://", Liquidsoap will properly use the "https" protocol
  when broadcasting to it.

- The reports available via the "Reports Overview" page are now also available as API calls to authenticated users with
  access to the reports pages themselves, via the following URLs:
    - Charts: `/api/station/{id}/reports/overview/charts`
    - Best and Worst Performing Tracks: `/api/station/{id}/reports/overview/best-and-worst`
    - Most Played Tracks: `/api/station/{id}/reports/overview/most-played`

- A new API endpoint is available to view _all_ broadcasts for a station, not just for a specific streamer:
    - `/api/station/{id}/streamers/broadcasts`

## Bug Fixes

- A bug preventing unique listeners from appearing when using remote relays powered by the non-KH branch of Icecast
  2.4.x has been resolved (#3700).

- Advanced custom configuration for Icecast frontends supports both singular and multiple `<alias>` definitions (#4223).

- Ansible installations will also properly be updated to MariaDB 10.5 and new installations will properly restart PHP
  8.0.

- Fixed a bug with the 5-minute sync's Check Media task taking an inordinately long amount of time with remote
  filesystems (like S3 or Dropbox). (#4212)

- Some issues causing errors about type mismatches (caused by recent implementations of strict typing) have been
  resolved.

- The "Clone Station" feature has been fixed and expanded.

- A bug that caused the URLs in "Now Playing" API responses to occasionally jump from using the "Prefer Browser URL"
  setting to not using it has been resolved; the API response should now be far more consistent.

---

# AzuraCast 0.12.4 (Apr 27, 2021)

## Code Quality/Technical Changes

- **NowPlaying API Change**: Previously, we maintained 3 values for listener counts: current, unique, and total. Since
  these three data sources came from 2 actual measurement units (total and unique), this was often confusing to our
  users. We have simplified our implementation of this to simply show `total` (all listeners, not filtered for
  uniqueness) and `unique` (the total distinct number of listeners). For legacy purposes, the `current` variable remains
  in responses, but is equal to the `total` variable.

- Both Ansible and Docker installations have been upgraded to PHP 8.0.

- The PHP-SPX (Simple Profiling eXtension) extension has been added and can be enabled via a new environment variable in
  `azuracast.env`; this will allow very simple visual profiling of the application and its memory/CPU usage over time.

- Several high-traffic transactions (such as saving updated listeners) are now transactional, resulting in a significant
  performance boost for larger stations.

- To improve performance, the internal queue used for station playlists has been moved from a standalone array to being
  attached to the `StationPlaylistMedia` entity.

- To improve performance and fix several race condition errors, settings has been migrated from its previous structure
  to a single flat database row that uses regular Doctrine EntityManager functionality, greatly simplifying the relevant
  code.

## Bug Fixes

- In the Media Manager, when listing media in a single playlist, the manager will show other playlists associated with
  the media (#4079, #3841).

- Issues with Dropbox filesystems used as Storage Locations have been resolved (#4026, #4057).

- An issue preventing broadcast recordings from saving correctly has been identified and fixed (#4055).

- Various issues where errors weren't shown on Vue-powered pages have been fixed.

- Errors with retrieving Now Playing data are now more consistently handled across the system.

- AzuraCast can now properly read _and write_ metadata to/from Flac and Ogg Vorbis files without issue.

- Disabled HTML5 in-browser validation for multi-tab forms, preventing a condition where you could not submit a form but
  could not currently see the invalid form control that prevented submission.

---

# AzuraCast 0.12.3 (Apr 14, 2021)

## New Features/Changes

- **Twitter Player Preview**: If you include the URL of your station's public player page in a tweet, a player component
  will automatically appear in the tweet that includes an embedded player for your station.

- **Embed Widgets**: A new "Embed Widgets" modal has been added to the station profile that will let you customize your
  embeddable widgets and show you a preview of their rendered status.

- You can now embed playback history as a standalone component in your web site.

- By default, avatars will be served from the free and open-source [Libravatar](https://libravatar.org) service. You can
  configure the external avatar service from the system settings, along with the default avatar URL.

- The "Average Listeners" and "Unique Listeners" charts on the dashboard are now "zoomed in" to show the last 30 days;
  if you want to view older data, simply click and drag the chart to view older data.

- The SoundExchange report will once again automatically retrieve the ISRCs for tracks with no ISRC assigned (now using
  the open MusicBrainz API database).

## Code Quality/Technical Changes

- In preparation to support PHP 8.0, we have updated to version 2.0 of the Flysystem filesystem abstraction library.

- We've switched from the `material-icons` library to the `@material-icons/font` library. In particular, we are using
  the "two-toned" version of the Material Design icons across the application.

- Instances of the "Pause" icon across the system have been replaced with the "Stop" icon to more properly indicate what
  they do.

- Heavy performance optimizations have been made in the following areas:
    - Looping through, and processing, station media (5-minute sync)
    - Processing listeners for stations with large listener counts
    - The AutoDJ queue building process

## Bug Fixes

- Fixed a minor bug with the `is_now` parameter on the Schedule API endpoint.

- Fixed a number of bugs relating to how the AutoDJ queue is built.

- Fixed bugs relating to playlist folder auto-assignment.

- When saving changes to a file that does not use ID3 metadata, users will no longer encounter a processing error
  (#3798).

---

# AzuraCast 0.12.2 (Mar 9, 2021)

## New Features/Changes

- **E-mail Delivery**: System administrators can now configure SMTP for e-mail delivery via the system settings page. If
  SMTP is enabled for your installation, the following functionality is added:

    - **Self-Service Password Reset**: Users can request a password recovery token to reset their own passwords.

    - **E-mail Web Hook**: You can dispatch an e-mail to specified recipients as a web hook when specific triggers
      occur.

- Web Hooks can now be triggered to dispatch when a station goes offline or comes online.

- You can now generate listener reports for specific time periods instead of just day ranges.

- For sequential or shuffled playlists, you can now view the internal queue that the AzuraCast AutoDJ uses to track its
  song playback order from the "More" dropdown next to the playlist.

## Code Quality/Technical Changes

- We have removed the "?12345678" cache-busting timestamp query strings appended to the end of stream URLs. These have
  caused a fair amount of confusion over the years, and with our modern playback controls (and with modern browsers)
  it's far less necessary than it used to be.

- Logging has been improved for critical errors (i.e. "out of memory" or "execution time exceeded").

- We have improved the visibility and usability of our password strength meter where it is used.

- **API Change**: The Now Playing API response now has a boolean "is_online" value to indicate whether we are currently
  detecting a broadcast from the station.

- Liquidsoap has been updated to version 1.4.4 stable, and the SFTPGo library has been updated to its latest version.

## Bug Fixes

- An issue with some stations crashing shortly after startup has been resolved. This was caused by a safety check we
  added to the AutoDJ to check that AzuraCast was up and running at the same time; however, this caused issues with
  stations that don't use the AzuraCast AutoDJ (i.e. stations that stream live or use remote playlists).

- We have identified an issue that would prevent backups from older than a few months ago from restoring correctly; this
  issue has been resolved, so backups should now restore without any issue regardless of the backup's age.

- Several issues causing slowness in the Listener Report (especially the CSV generation) have been improved, so stations
  with large listener counts should still be able to take advantage of this report in more scenarios.

- Fixed a bug in the Now Playing adapter that would cause stations to return as offline when using the Icecast adapter
  with no administrator password set.

- Fixed a bug that prevented metadata from writing back to media files when album art was set.

- A bug preventing the charts on the dashboard from showing or hiding properly has been fixed.

---

# AzuraCast 0.12.1 (Feb 19, 2021)

## New Features/Changes

- In the Now Playing API response, the station's public-facing URL and URLs to download the PLS and M3U playlists for
  the station are included in the response.

## Code Quality/Technical Changes

- Across all AzuraCast repositories, the `master` branch has been renamed to `main`.

- A new section has been added to the "Edit Liquidsoap Configuration" panel at the very bottom of the configuration,
  after all broadcasts are sent out.

- The "Enable Advanced Features" environment variable, which never actually worked correctly, has been moved to a
  database-managed setting manageable via the "System Settings" page, and now works as intended. For new installations,
  this option is unchecked by default, but can easily be enabled for "power users".

## Bug Fixes

- Calling `DELETE` on the files API endpoint properly deletes the file itself (#3813).

- An issue with the updated dashboard has been fixed, bringing the dashboard appearance closer to the old visual style
  but while still being a modern Vue component.

- Changes to the weighted shuffle algorithm were reverted after further evaluation.

- The AutoDJ queue timing has been reworked and simplified and issues have been fixed relating to cue timing.

- Playlist weighting (1-25) now properly weights playlists with 1 being the _least_ frequently played and 25 being the _
  most_ frequently played, as is intended and described in the documentation. (#3735)

- Safety checks have been added to the AutoDJ to prevent the same track from being played consecutively. (#3682)

- All web hooks now implement a rate limit to never send more than once every 10 seconds.

---

# AzuraCast 0.12 (Jan 27, 2021)

This update introduces significant new features and fixes a number of bugs reported by the community.

## New Features/Changes

- **Remote Album Art Retrieval**: If enabled in the system settings panel, AzuraCast will now check remote services to
  attempt to retrieve album art if it is missing, or not provided (i.e. for live DJs). By default, this system uses the
  MusicBrainz database, which is comprehensive but can be slow; if you provide an API key for the last.fm API, AzuraCast
  will prefer the last.fm API for album art instead.

- **Media Manager Improvements:** Some changes have been made to the media manager to improve the user experience and
  accessibility:
    - You can now edit the playlists associated with a track from directly within the "Edit" modal dialog box for that
      track.
    - If all tracks/directories selected are in a playlist, that playlist will be checked by default in the "Set
      Playlists" dropdown.
    - Media uploaded via the Media Manager and Station programmatic names will no longer aggressively escape UTF-8
      characters, and will instead leave them intact in most cases.
    - You can now instruct AzuraCast to re-analyze and reprocess the selected media files in the Media Manager.

- The "Duplicate Songs" report has been merged into the Media Manager, so you can take full advantage of the rich
  filtering and other tools available in the Media Manager when addressing duplicate tracks.

- You can now view all "Unprocessable" media in a single report; this includes non-music files (like images) and any
  media that has errors that prevent us from processing them.

- You can disable the "Download" button on the "On-Demand" media page while leaving streaming enabled by editing the
  station profile.

- You can show or hide the charts on the dashboard, and sort and filter stations listed there.

- Listeners are now tracked by the mount point/remote stream they're connected to, which is shown in reports.

- **Google Analytics**: A new webhook has been created that will automatically post live listeners to your Analytics
  property. This is only compatible with "Universal Analytics" properties (codes that begin with GA-).

## Code Quality/Technical Changes

- Mount points that are hidden from public view are also hidden on the Icecast status overview page.

- Unprocessable media is now stored in a separate database table along with the date/time processed and the relevant
  error that prevented the file from being processed. This will prevent a situation where numerous files are non-
  processable but are processed in every 5-minute sync. AzuraCast will automatically re-check files marked as
  "unprocessable" if their modified time updates (i.e. the file is reuploaded) or approximately a week passes.

- In preparation for the PHP 8.0 update and for other technical reasons, we have made some library changes:
    - Switched PSR-6/PSR-16 cache implementation to the `symfony/cache` component.
    - Removed the `studio24/rotate` and replaced with custom implementation for Flysystem.
    - Switched from custom paginator to the `pagerfanta` library.
    - Switched from custom image manipulation to the `intervention/image` library.
    - Switched from custom crawler/bot detection to the `matomo/device-detector` library.

  If you are building a plugin that uses the cache, as long as you are using the PSR interfaces, no change will be
  required, but other updates may be required to your codebase.

- The Docker Utility Script (`./docker.sh`) will now ask before running `docker system prune` post-update.

- For more advanced setups, you can now set the following environment variables in `azuracast.env` to use a third-party
  Redis service instead of the one bundled with AzuraCast:
    - `REDIS_HOST` (default: `redis` for Docker, `localhost` for Ansible)
    - `REDIS_PORT` (default: 6379)
    - `REDIS_DB` for the database index (default: 1)

- There is a new debug CLI command, `azuracast:debug:optimize-tables`, which optimizes all tables in the MariaDB
  database and can recover space that's no longer in use.

## Bug Fixes

- Hidden mount points and relays will still be shown on the profile page.

- If your browser sends a locale like `fr` instead of `fr_FR`, it will now be supported and detected (#3558).

- Fixed a bug where sometimes changes to media metadata would be saved, only for the next 5-minute synchronization
  process to revert to the previous data (#3553).

- Issues with the Media Manager not showing files correctly when they were shared between stations has been fixed.
  (#3618)

- Fixed a bug where the first theme switch doesn't actually switch the theme.

- Fixed an issue with Ogg Opus streams not continuing to play. (#3597)

---

# AzuraCast 0.11.2 (Dec 11, 2020)

This update includes some minor new features but resolves significant bugs identified in version 0.11.1. Updating is
recommended for all users.

## New Features/Changes

- **Dropbox Storage Locations**: Dropbox is now supported as a remote location for storage locations, which can hold
  station media, station live broadcast recordings and system backups.

## Bug Fixes

- A major issue that caused multiple Message Queue workers to lock up and fail to process new messages (including new
  media) has been identified and fixed.

- Issues with viewing (#3526) and dispatching (#3535) webhooks have been fixed.

- Ansible installation issues (#198, #3517, etc.) have been resolved.

- The settings retrieval process has been reworked to avoid collisions when saving changes (#3525).

- An issue causing the Audit Log to log _all_ settings changes, flooding the audit log with automated settings changes,
  has been fixed, and the fix will clean up the excess records as it's applied (#3545).

- Importing playlists from existing M3U/PLS files works correctly again (#3528).

- A bug preventing stations from being cloned has been fixed (#3501).

- The SoundExchange royalties report has been updated and is working again (#3552).

---

# AzuraCast 0.11.1 (Dec 7, 2020)

## New Features/Changes

- **Remember Me**: You can now select the "Remember me" button when logging in to extend your session to two weeks
  without needing to log in again.

## Code Quality/Technical Changes

- Previously AzuraCast's codebase had a `Settings` class and a `Settings` entity that managed two separate things, which
  caused quite a lot of confusion. The former `Settings` class is now named `Environment` (as it contains
  environment-specific settings) and the `Settings` database entity has been made strictly typed and the settings have
  been migrated to a new naming convention.

- To improve performance when processing large media collections, waveforms won't be processed during the initial media
  processing step and will be processed on-demand.

## Bug Fixes

- New installations won't see warnings about backups and sync tasks immediately after installation.

- Various issues with processing some types of media have been resolved.

- When manually running a synchronization task from the "System Debugger" page, the task will run in the backgroun and
  progress will be displayed on a screen that refreshes periodically. This will avoid timeout issues.

---

# AzuraCast 0.11 (Nov 28, 2020)

This release includes many contributions from members of our community as part of the annual Hacktoberfest event, where
we selected a number of items that our core developer team, along with the community submitting pull requests, could
work on during the month.

## New Features/Changes

- **Media storage overhaul**: The way media is stored and managed has been completely changed:
    - Station media, live recordings, and backups have "Storage Locations" that you can manage via System
      Administration.
    - Storage locations can either be local to the server or using a remote storage location that uses the Amazon S3
      protocol (S3, DigitalOcean Spaces, Wasabi, etc)
    - Existing stations have automatically been migrated to Storage Locations.
    - If more than one station shares a storage location, media is only processed once for all of the stations, instead
      of being processed separately.

- Statistics now include the total _unique_ listeners for a given station in a given day. On the dashboard, you can
  switch from the average listener statistics to the unique listener totals from a new tab selector above the charts.

- There is a new, much friendlier animation that displays when Docker installations of AzuraCast are waiting for their
  dependent services to be fully ready. This avoids showing the previous messages, which often looked like errors, even
  though they weren't.

- **Security Update:** The global "Administer Users" and "Administer Permissions" permissions have been *removed* from
  the system. In both cases, users who had this permission could effectively make themselves a super administrator user,
  so user and permission management is now only accessible by users with the "Administer All" permission.

- **Security Update:** A possible arbitrary code execution issue with Liquidsoap DJ authentication is resolved by
  changing the way input is escaped. (#3465)

- Simplified Chinese support is now available in the web interface.

## Code Quality/Technical Changes

- **Removal of InfluxDB**: Despite the ever-increasing complexity of AzuraCast, sometimes we review our dependencies to
  ensure if the additional load and maintenance overhead they cause is worth the benefit to our system. In the case of
  our InfluxDB time series database, we decided that this dependency was no longer needed, and its purposes could be
  fully handled by our existing MariaDB database. This decision was also motivated by Influxdata releasing a
  non-backwards-compatible new version of the software, which would present a very challenging migration to our users.
  Instead, we have removed the dependency entirely.

- **Multiple code quality improvements**: We have incorporated stricter code standards checking into our continuous
  integration (CI) process and updated our code accordingly, so any code that publishes to stations will meet a
  stringent set of code style, standards, static analysis and pass our normal test suites.

- **Songs table overhaul**: A `Songs` table has existed for the entirety of AzuraCast's existence as the authoritative
  source of spelling and capitalization for all song titles, artists, etc. This solution was not scalable to large
  stations and there was no effective way to clean up this table once it became vastly oversized. This update removes
  the `Songs` table entirely and relocates its attributes to the various tables that used it before (song history,
  request queue, etc).

- **File Operations API Changes**: In order to clarify the meaning of parameters, the `file` parameter for the following
  API endpoints has been changed to `current_directory`:
    - `/api/station/{id}/files/list`
    - `/api/station/{id}/files/directories`
    - `/api/station/{id}/files/batch`
    - `/api/station/{id}/files/mkdir`
    - `/api/station/{id}/files/upload`

- SweetAlert has been updated to SweetAlert2; alert prompts will now also have the same theme as the current active
  theme on the page.

- Update checks have been simplified; if you are on the "rolling release" channel you will see rolling release updates,
  and if you are on the stable channel you will see stable version releases only.

- When adding tracks to, or removing tracks from, a playlist, its current playback queue will be more intelligently
  updated instead of being reset completely, which was much more likely to lead to duplicate artist/title playback.

- The SASS (specifically, SCSS) code of our underlying theme has been greatly simplified and updated to include some
  features from the Bootstrap 4.5 library.

- The station profile and public player are now fully converted into Vue components, which improves their performance
  for many users and allows us to much more easily make changes to them in the future, including making the public
  player customizable by users embedding it into their own websites.

- Plugins can now add their own ACL permissions via the new `App\Event\BuildPermission` event.

## Bug Fixes

- If the current stable release uses a different `docker-compose.yml` file than the current rolling release,
  the `docker.sh` utility script will accommodate for this. Make sure to run `./docker.sh update-self` before any
  updates to ensure the script is up-to-date.

- The "Move" batch command in the media manager has been fixed to work as expected; if you select to move a folder to
  another one, it will move the entire folder instead of just moving its contents over.

- Several optimizations have been made to how media is processed (and what media isn't processed by the system), which
  should improve performance and avoid repeatedly processing files that can't be processed.

- Exceptions thrown when processing items in the Message Queue are now logged appropriately.

- When importing a playlist from a PLS/M3U file, the order of the imported files will be preserved in the playlist.

---

# AzuraCast 0.10.4 (Oct 1, 2020)

This release is being tagged to incorporate all of the feature improvements, bug fixes and other changes we've made to
AzuraCast over the last few months into a stable release just prior to our Hacktoberfest event. We expect the
Hacktoberfest-related changes to be significant and to require a significant amount of testing, so we wanted to ship a
stable version before making these major changes.

## New Features/Updates

- Significant work has been done to rewrite the core components of the AutoDJ, scheduler, and message queue dispatcher.
  This should greatly improve the reliability and accuracy of our track playback among other improvements.
- Raspberry Pi support has been restored, and AzuraCast's Ansible installation is once again compatible with Raspberry
  Pi 3B and 4 devices. (#3048, #3028)
- Added a "not backed up recently" notification to the application dashboard and a reminder to back up during the update
  process. (#2756)
- You can now only enable the "Force HTTPS" setting when visiting the page from an HTTPS connection, preventing you from
  immediately locking yourself out. (#2932)
- You can now append `?theme=dark` or `?theme=light` to the query string of any public page to change its theme,
  regardless of your branding settings or user preferences.
- Media Manager performance has been improved if you have large subfolders with many files in them.

## Bug Fixes/Other Changes

- Minor security fix: If you are a user with the global "Administer Users" permission but not the global "Administer
  All" permission, you will no longer be able to impersonate a user with higher permissions than yourself, thus granting
  you those permissions. (#3097)
- Fixed a bug where you could continue into AzuraCast without finishing setup. (#2958)
- Icecast now uses the same SSL certificate your web connection uses via LetsEncrypt setup. (#2969)
- Connecting to a Shoutcast 1 remote relay works again. (#2989)
- The "Play" icon will now properly switch between playing and not-playing states for only the actively playing item. (
  # 3170)

---

# AzuraCast 0.10.3 (Jun 21, 2020)

## New Features and Infrastructure Changes

- **On-Demand Streaming/Download**: You can now allow on-demand streaming of certain playlists, which will allow your
  audience to listen to and download tracks from the specified playlists at any time. First, enable on-demand broadcasts
  on the station's profile, then enable it on every playlist that contains tracks you want to be available for on-demand
  download. Keep in mind any copyright restrictions that may apply in your area.

- The visual cue editor for media has been reworked from the ground up and now uses a server-side service to generate
  the waveform graphics for each track where possible. This improves the stability and reliability of this tool greatly.

- We have merged the configuration from our "multi-site" setup into our core Docker Compose configuration. This means
  that every Docker installation with an up-to-date `docker-compose.yaml` file has an nginx reverse proxy included,
  which allows for easy automated renewal of LetsEncrypt certificates and support for multiple Docker containers serving
  multiple domains alongside your AzuraCast installation. See issue #2855 for instructions on migrating your existing
  SSL certificates into this new infrastructure.

- Static assets (Vue components, scripts loaded from NPM, etc) are now built automatically with each new commit so they
  will be up-to-date. For most users, this won't be a noticeable change, but is important to note for developers. On
  Docker, this build happens as part of our GitHub action and is included in the image you pull when updating; on
  Ansible installations, your server itself builds the static assets.

- After working with the Liquidsoap team, we have moved "Remote Stream" playlist types to be exclusively handled by
  Liquidsoap, which should improve their functionality.

- There is a new CLI command to list all accounts on an AzuraCast installation. For Docker users, this can be invoked
  via `./docker.sh cli azuracast:account:list`.

## Bug Fixes/Other Changes

- The "base URL" on the settings pane now includes the URL scheme (`http://` or `https://`) so you can prefer one or the
  other without forcing HTTPS for the entire web application. #2814

- Icecast now has the proper "hostname" value set. #2839

- Fixed a minor issue with the TuneIn web hook not sending album art. #2863

- Fixed an error causing UNIX timestamps to appear on certain tables instead of formatted times. #2866

- Fixed a number of areas that had issues with translated strings. #2945 #2946 #2957

---

# AzuraCast 0.10.2 (May 6, 2020)

This is a primarily bug fix oriented release that includes several important improvements. All users of AzuraCast are
encouraged to update to this version, especially users affected by the issues listed below.

## Upstream Dependencies

- Liquidsoap has been updated to version 1.4.2 on both Ansible and Docker installations, fixing a critical bug that
  caused stations with numerous playlists to fail to be able to start.

- Icecast has been updated to use the latest release from Karl Heyes' "KH" branch, 2.4.0-KH14.

## New Features

- You can set a default album art URL on a per-station level; if one is not set at the station level, the system-wide
  one will be used instead (and if one is not provided there, the default AzuraCast album art image will be used
  instead).

- A new API endpoint has been created that will allow you to update the current metadata being played programmatically.
  The endpoint can be accessed via `/api/station/(station_id)/nowplaying/update` and requires an API key with the "
  Manage Station Broadcasting" permission. A future version will further document the capabilities of this endpoint, but
  it is already available for initial use in this version.

- The AzuraCast homepage will now remind you if you haven't run any sort of backup (either manual or automated nightly)
  in at least the last two weeks. (#2756)

## AutoDJ Changes/Fixes

- The AutoDJ playlist weighting algorithm has been slightly tweaked to include lower-weighted playlists more frequently.

- The system debugger page now has a "Rebuild Queue" button that will erase the current upcoming song queue and build a
  new one, while providing full debugging information on the screen. If you are having issues with your scheduled
  playlists not playing, you should use this function to help determine how the AutoDJ is running.

- The "Cued" time in the upcoming song queue will now accurately reflect when AzuraCast expects the track to actually be
  played.

- Requests will be added into the queue at the appropriate time. (#2772)

---

# AzuraCast 0.10.1 (Apr 26, 2020)

## Bug Fixes

- This very minor release fixes an issue caused by jQuery 3.5.0 that prevented dropdowns from working correctly.

---

# AzuraCast 0.10.0 (Apr 26, 2020)

AzuraCast 0.10.0 is an incremental release with several bug fixes and improvements across the application. This version
introduces minor changes to the AzuraCast API (see below) and is tagged as a new minor release as a result.

## New Features

- **Ubuntu 20.04 LTS Support**: The latest long-term-support (LTS) version of the Ubuntu linux distribution was released
  this week, and we're already updating the various components of our application to support it. Our primary Docker
  images already run on Ubuntu 20.04 and our Ansible installation supports 20.04 for new installations. If you're
  starting a new station, using Ubuntu 20.04 is strongly recommended.

- AzuraCast's AutoDJ will now build a queue of songs ahead of the current song. Instead of just seeing the "next" song,
  you can view the entire upcoming queue (via `Reports` > `Upcoming Song Queue`) and remove items from it. The length of
  the queue is customizable on a per-station level.

- A default minimum request threshold of 15 seconds has been added to prevent users from flooding your request
  infrastructure even if you have no threshold set. You can also now clear all pending requests via a single button
  click in the web interface.

- You can manually trigger the reshuffling of "Shuffled" playlists via the "More" dropdown menu next to the playlist in
  the web UI.

## Bug Fixes/Other Changes

- The "Edit Liquidsoap Configuration" page has been restricted to users with the "Manage Station Broadcasting"
  permission instead of all station managers. Be aware that users with this permission can not only control the status
  of their own station's frontend/backend services, but via editing custom Liquidsoap configuration can also possibly
  impact other stations on the same installation.

- Several improvements have been made to scheduling and the AzuraCast AutoDJ in general (#2740, #2689, #2631). This is
  an ongoing work in progress; if you continue to encounter issues with your installation not properly adhering to
  scheduling, please see super-issue #2631.

- API responses are now strictly typed and will more reliably conform to these type requirements. This makes API
  responses more useful if you're using or importing them into strictly typed languages. A majority of the API responses
  haven't fundamentally changed with the exception of having stricter types.

- Fixed a bug where non-public stations would trigger the Now Playing API response returning in a non-standard format. (
  # 2709)

- Metadata from previous DJs won't be played back when the next DJ connects. (#2728)

- Actions that will disconnect listeners (i.e. Restart Broadcasting) now have a modal confirmation dialog.

- Songs that contain the pipe (`|`) character won't cause errors with Liquidsoap. (#2631)

---

# AzuraCast 0.9.9 (Mar 14, 2020)

## New Features and Important Changes

- **Auto-Assign Folders to Playlists**: If you select a folder in the Music Files manager and add it to one or more
  playlists, any songs uploaded inside that folder (via the web interface or SFTP) will automatically be added to that
  playlist. If the files in the folder are also in other playlists, they won't be removed from those playlists, just
  added to the folder's playlists.

- **Streamer Schedules**: You can now set scheduled times when streamers are supposed to be broadcasting. You can choose
  to "enforce" this schedule, which means that the streamer/DJ can only connect during their scheduled time and no other
  times.

- **Schedule View**: Now that both playlists and streamers are scheduled, you can use AzuraCast to produce a full
  schedule lineup for your station including both upcoming scheduled playlists and scheduled DJ/streamers. We have
  created new schedule API endpoints to facilitate this, and added a new "Schedule" section to station profiles.

- **Record Live Broadcasts**: Thanks to several improvements within AzuraCast, we now track the start and end times for
  every DJ/streamer broadcast. You can view the full history for each streamer by clicking the "Broadcasts" button next
  to their name. We also now offer the ability to automatically record live broadcasts; the recording of each given
  broadcast can be viewed in the same "Broadcasts" panel. Also, when viewing the song playback timeline, you will be
  able to see which DJ/streamer was live when a particular track was broadcast.

- **Advanced Liquidsoap Configuration Editor**: We've replaced the single "Advanced Custom Configuration" field in the
  station profile with a newer, much more powerful editor tool for directly editing Liquidsoap configuration. This
  editor shows the automatically generated configuration alongside your custom code, and allows you to inject custom
  code in multiple new places.

- **Visual Cue Point Editor**: Thanks to a pull requested contribution from Bjarn Bronsveld, you can now edit a music
  file's cue-in/cue-out/fade-in/fade-out points with a rich, interactive waveform viewer that shows markers for each of
  the specified points. This offers a significantly improved experience over the previous timestamp input fields (which
  can still be manually edited if necessary).

- **New IP Geolocation Options**: With the changes to the MaxMind GeoLite database, we've abstracted out how we perform
  IP geolocation. By default, all Docker installations come with the DBIP lookup tool, which is suitable for a majority
  of use cases. If you prefer GeoLite for its result accuracy, you can enter a license key acquired from their web site
  and AzuraCast will download and use the GeoLite database instead.

## Bug Fixes and Minor Improvements

- The `azuracast_web_v2` Docker image is now directly tied to the main code repository, so rolling-release updates are
  much faster and involve far less load time.

- Multiple playlists can now have the same name (#2281).

- Several bugs with station cloning have been fixed and the feature is now again fully functional. (#2276, #2427)

- The "Prefer Browser URL" and "Enable SFTP Server" settings have been tweaked so they will behave more sensibly, and
  will not interfere with portions of AzuraCast's web interface that make API calls.

- Several sections of AzuraCast (including, notably, the Duplicate Songs report and Radio Automation code) have been
  heavily optimized to avoid memory overflow with very large music libraries. (#2003)

- Liquidsoap has been updated to version 1.4.1.

- Quotas are now enforced within the built-in SFTP server. (#2315).

- You can now directly provide an amplification value that Liquidsoap will use on each track from the media manager. (
  # 2334)

- The AzuraCast AutoDJ's scheduling code has been significantly overhauled to be easier to maintain and to fix a number
  of "edge case" scenarios; you should now see much more accurate track selection that more reliably avoids duplicate
  artists/tracks while still preferring higher weighted playlists.

- In situations where files are served directly from the filesystem, we now take advantage of nginx's
  built-in `X-Accel-Redirect` functionality to instruct nginx to serve the file directly from disk instead of passing it
  through PHP; this results in _much_ faster and more reliable downloads for media previewing, album art, historical
  broadcasts, backups, and more.

- In newer Vue components (i.e. the media manager, playlist manager, streamer manager) modal dialog boxes will autofocus
  on the relevant input, and the enter key will submit the modal dialog's form as expected (#2449).

- When previewing a music track or other finite-length file within the AzuraCast web interface, you can seek through the
  file using a new progress slider alongside the player controls in the header menu.

- You can now make the Listener map fullscreen if desired.

---

# AzuraCast 0.9.8.1 (Jan 11, 2020)

This release includes some infrastructural changes to the application, along with several bug fixes.

## Changes

- **Updated to PHP 7.4:** With the general availability of the latest version of PHP, version 7.4, we wanted to update
  as soon as possible to take advantage of the performance improvements and new code features available. All
  installations running this version or later will be on PHP 7.4 or newer.

- **Switched Built-in FTP Service to SFTP:** We encountered a number of issues with our previous built-in FTP service,
  from trouble with the passive IP range to issues forwarding traffic and some problems with incomplete files being
  uploaded. These are all resolved with our switch to SFTP, which uses one port (by default, 2022, though this is
  modifiable by changing/adding `AZURACAST_SFTP_PORT` in `.env` on the host) to handle all incoming connections, is a
  more modern and secure specification, and supports so-called "atomic" copies, where files are only moved into the
  destination folder after they are fully uploaded, preventing partial processing errors. The only change from a user
  experience is that now you must create one or more SFTP-specific accounts for each station rather than using your
  existing AzuraCast credentials.

- **GeoLite IP Geolocation Database Changes:** The provider of the free GeoLite database, MaxMind, decided to change (
  with very short notice to users) how they supply the GeoLite IP geolocation databases that we use to add listener
  location and map coordinates to our Listeners report. This means we can no longer automatically package it with our
  installations. Instead, if you want to use the Listeners mapping feature, you must visit the new "Install GeoLite
  Database" page in system administration and supply a license key, available for free from the MaxMind site. We will
  then automatically download the latest GeoLite database, and keep it updated for you.

- The mini music player inside the AzuraCast interface is now inline with the header menu instead of accessed via a
  dropdown menu.

- All uncompiled static frontend assets (i.e. Vue code, unminified Javascript, SASS and CSS) have been moved out of the
  web root and into their own `/frontend` folder. This should have no impact on a vast majority of users, but if you are
  looking to make custom changes to how the application is styled, be aware that those files are now located somewhere
  new.

## Bug Fixes

- Updated the Total Listener Hour calculation to be much smarter about overlaps. #2186
- Fixed an issue where filenames, once converted from Unicode, were longer than 255 characters and broke things. #2205
- Fixed some issues with automatically assigned custom fields. #2207
- Fixed localization not working properly on several new frontend Vue components. #2252
- Fixed a bug preventing stations from being cloned with SFTP users. #2276
- Fixed a minor bug where "Please wait..." still showed if you restarted broadcasting on some pages. #2262

---

# AzuraCast 0.9.8 (Nov 23, 2019)

## New Features

- **Updated to Liquidsoap 1.4.0**: Both Ansible and Docker installations have been updated to version 1.4.0 of
  Liquidsoap. This new version introduces smarter crossfading, better UTF-8 support and a number of quality-of-life
  improvements. Some of the syntax of the new version is not backwards-compatible with the previous versions, so
  updating is highly recommended for all users to avoid errors.

- **New Playlist Manager**: Alongside the new Media Manager released in the previous version, we have also rebuilt the
  Playlist manager to be a fully interactive Vue.js component. This allows for inline editing, reordering, and removal
  of playlists, and support for the new scheduling features detailed below.

- **Playlist Scheduling Improvements**: All playlist types can now be scheduled, and any single playlist can now have
  multiple schedule entries. Schedule entries can also have a start and end _date_, for limited-time announcements or
  seasonal content.

## Bug Fixes/Updates

- Increased the timeout allotted to backups (#2149, #1717) and backup restoration (#2166).
- The built-in Docker FTP service will now use the correct external IP for PASV connections, so it will be compatible
  with significantly more clients.
- When specifying times for media fade-in/fade-out/cue-in/cue-out, you can specify the time in mm:ss format rather than
  in total seconds if you prefer (#2117).
- Various accessibility improvements across the system.
- Saving playlists will no longer block the web request, and will be handled instead by a separate worker process,
  allowing for larger playlists to be saved more frequently (#2068).
- Modified the way we check for the latest CA certificates to avoid stability issues.
- Fixed a bug where clicking breadcrumb navigation in the Media Manager would show all files as "not processed" (#2086).
- Avoid writing to a temporary directory in cases where a song is already locally stored on the server, which should cut
  down significantly on temporary directory sizes.

---

# AzuraCast 0.9.7.1 (Oct 12, 2019)

## Bug Fixes

- This is a minor version release that includes updated Packagist dependencies and resolves issues that prevented
  Ansible ("Traditional") installations from proceeding. Updating to at least version 0.9.7 is recommended for all
  users, and 0.9.7.1 is necessary for new users installing via the (mostly unsupported) Ansible installation method.

---

# AzuraCast 0.9.7 (Oct 11, 2019)

## New Features

- **New Media Manager**: The Media Manager is one of the core parts of the AzuraCast experience, and we want it to be as
  smooth and usable as possible, so we rebuilt it from the ground up using the Vue frontend framework. The new media
  manager features snappier response times, tooltips on buttons, a new "add to playback queue" button, inline renaming
  of directories, and a brand new inline media editor and album art manager that lets you customize tracks without ever
  leaving the main media manager page.

- You can now customize even more of the AzuraCast Docker installation parameters by modifying configuration lines in
  your local [azuracast.env](https://github.com/AzuraCast/AzuraCast/blob/main/azuracast.sample.env#L70-L80) file.

## Bug Fixes

- We have resolved a major issue with one of our third-party libraries that causes "No valid bundles" errors to appear
  any time AzuraCast attempts to connect to a secure URL. This can happen even on pages that don't seemingly make any
  external connections (i.e. the media manager), because these pages depend on third-party services (i.e. IP address
  resolution) to display some part of their data. Updating is strongly recommended to resolve this issue.

- An issue causing updates to fail because of outdated cached Doctrine configuration has been resolved.

- Several parts of the system have been optimized to handle large library sizes without running out of memory. This is
  an ongoing project that will require more time to complete.

## Technical Notes

- This version includes a significant number of code quality improvements under the hood. You won't notice many of these
  changes (including optimized Redis session handling, new dependency-injected CLI commands and entity repositories),
  but they are meant to make AzuraCast's code much more consistent and easier to maintain, which means faster bug fixes
  and more new features!

---

# AzuraCast 0.9.6.5 (Aug 27, 2019)

## New Features

- This incremental minor version release adds support for AzuraCast's built-in FTP server on Docker installations, which
  allows you to use your AzuraCast credentials to log in and manage media via a high-performance FTP server.

---

# AzuraCast 0.9.6.2 (Aug 27, 2019)

This is a minor incremental release that offers some bug fixes and improvements to the 0.9.6 version release.

## Bug Fixes/Other Changes

- Fixed an issue with remote-only streams not properly showing their now-playing data.
- Fixed a bug where media deleted via FTP/SFTP wouldn't be cleared from playlists.
- Improve performance of the Redis cache by consolidating to a single active database connection.
- Further improve Redis performance by preventing unnecessary session creation under the hood.
- Make the Statistics Overview page use the station's time zone.
- Add support for the Turkish locale.
- Add localization to API responses and CLI commands, where possible.

---

# AzuraCast 0.9.6.1 (Aug 16, 2019)

This is a minor release to ensure that all users are on the latest version. It includes a few new features and several
bug fixes from version 0.9.6.

## New Features

- **Audit Log**: We now keep track of changes made to important database tables (stations, users, settings, mount
  points, relays, custom fields, etc.) and log them, along with the specific changes made and the user who made them, in
  a special reports table. You can view this report via the "Audit Log" link in the global system administration.

- **Per-Mount/Relay Listeners**: We have expanded our listener metrics to show the listener count associated with each
  individual mount point and remote relay on your station, so you can see which of your mounts/relays are generating the
  most traffic.

- **Total Listener Hours/Listener CSV Export**: We have added a "Total Listener Hours" calculation to our Listeners
  report, along with the ability for you to export the report's contents to a CSV file. Many areas use TLH as a
  measurement of radio royalty payments instead of the Actual Total Performances used by SoundExchange. Check with your
  local jurisdiction to determine the best way to report listeners for royalty payments.

## Bug Fixes/Other Changes

- By default, the new websocket/eventsource-driven "now playing" API is disabled on new installations, as it has been
  known to have some issues in certain hosting scenarios. You can always turn it on via the system-wide Settings panel,
  and if your installation is serving a large number of radio listeners, you are strongly encouraged to do so.

- The public radio player will now show a live DJ's name if one is live.

- Tweaked the AzuraCast AutoDJ's duplicate artist/title prevention algorithm to be more forgiving of playlists that
  either have no artist or have the same artist on all tracks.

- Under the hood, we have upgraded our underlying PHP framework (Slim) to version 4, which brings us into much closer
  compliance with a number of PHP Standards Recommendations, or PSR, specifications.

- The application has been almost completely translated into Russian.

- Updated our PLS/M3U file generation to properly include remote relays and show stream names on clients like VLC.

- Fixed a scenario where hundreds of "zombie processes" were being created by AzuraCast sending notification messages to
  the nchan websocket now-playing service.

- Fixed a bug where AzuraRelay relays kept being newly recreated every time the relay sent its "Now Playing" update to
  the parent server.

- Relays themselves (which were previously seen by the system as standard listeners) are now excluded from both listener
  reports and the total listener count.

- When determining your external-facing IP address (which requires pinging a service that's not located on your
  installation, one of the few times we do this), we have switched to using our own AzuraCast Central server for this
  service, so that we can avoid sending your IP to any third-party services. This service simply echoes back your
  external IP to you via JSON, and the IP isn't used for anything else.

---

# AzuraCast 0.9.6 (Jul 27, 2019)

It's only been just over a month since AzuraCast 0.9.5.1 was released, but we've made some very significant, very
important updates to the software in that time, especially in the fields of reliability and performance.

## New Features

- **The AzuraCast AutoDJ is Back**: A few versions ago, we had attempted to switch entirely to using Liquidsoap for our
  AutoDJ. We've come to realize in the months since then that we actually need our own AutoDJ management component for
  important reasons, so we brought it back. It's also been improved to include a few new features. Benefits of using the
  AzuraCast AutoDJ include:
    - Being able to see the next song that will play in the queue,
    - Applying playlist changes without reloading all of AzuraCast, and
    - Avoiding songs playing back-to-back with either the same title _or_ the same artist using our duplicate prevention
      system.

- **Introducing AzuraRelay**: This version of AzuraCast has built-in support for our new "relay-in-a-box"
  software [AzuraRelay](https://github.com/AzuraCast/AzuraRelay). Simply drop AzuraRelay onto a server that can run
  Docker, answer a few quick questions, and it will automatically connect to AzuraCast, detect all stations, and relay
  them all. It will also list itself as a relay on the "host" AzuraCast station, and report back listener and client
  details as well.

- **Two New NowPlaying API Sources**: Our most powerful and comprehensive set of data is all compiled into what we call
  the "Now Playing" API, which is a rich summary of the state of a radio station at the moment. To improve performance
  of more popular stations using our software, we've introduced two new methods of accessing this data: a static JSON
  file and a live Websocket/EventSource-driven plugin. You can read more on our
  new [Now Playing Data APIs Guide](https://docs.azuracast.com/en/developers/apis/now-playing-data).

## Bug Fixes and Minor Updates

- Icecast has been updated to 2.4.0-kh12.
- By default, the Docker installation has ports open for the first 50 stations, up from the first 10.
- Fixed an issue with automatic port assignment not working in some cases.
- Fonts are now locally hosted along with other static assets, which makes the entire installation self-contained.
- The currently playing song is written atomically to `/var/azuracast/stations/(station_name)/nowplaying.txt`, which is
  particularly useful for our [radio-video-stream](https://github.com/AzuraCast/radio-video-stream) example project.
- The Docker update script will now check for differences between the latest Docker Compose file and your local copy and
  only prompt if they're different, and will pull new images before bringing down existing ones, reducing station
  downtime during updates.
- Updated Ansible installation to ensure it works with Raspberry Pi 3/ARM64 devices.
- If you are leaving a page with a form on it that has unsaved changes, you will be prompted to confirm leaving the
  page.
- Web servers will now more aggressively cache the hashed static assets used in AzuraCast, improving performance.
- Added a new default album art image.
- Re-added the "clear playlists" button on media management, which does the same thing as clicking "Set Playlists" with
  songs selected, selecting no playlists, and clicking "Save".

---

# AzuraCast 0.9.5.1 (Jun 11, 2019)

This release is primarily a bug-fix release intended to resolve a number of issues identified in version 0.9.5. There
are a few minor but significant new features, and updating is highly recommended for all users.

## New Features

- **Live, Zero-Downtime Backups:** We have switched to a new format for creating backups that allows them to run without
  causing any outage for your listeners. This new backup format can be run directly from your web browser via the new "
  Backups" administration page, and you can also configure automated nightly backups. These new backups are portable
  between both Ansible and Docker installation methods, and can be used to migrate from Ansible to the recommended
  Docker installation; in fact, we have created a migration script (`docker-migrate.sh`) to do exactly that.

- **Station Clock:** You can now see a live updating clock in the station's time zone underneath the station name on the
  sidebar menu.

## Technical Notes

- The Highcharts library has been completely replaced across the entire application. This is largely because of
  Highcharts' license, which allows for free use for non-commercial entities but is not free software. Charts were
  replaced with the free and open-source `charts.js`, and the listener map was replaced with `leaflet`. This is the last
  component from early AzuraCast development that required updating to be a fully free and open-source stack.

- Significant portions of the application are now available in both Italian and Czech. A huge thank you as always is due
  to our translator volunteers for their contributions.

- Some forms across the system would trigger errors that weren't immediately visible (i.e. CSRF validation failures or
  errors on fields that aren't shown in the current tab); these errors are now displayed in a much more visible format
  and should be easier to spot.

## Bug Fixes

- Fixed a number of scenarios that caused the "This station is powered by AzuraCast" jingle to play despite a station
  having an established playlist of music. #1527 #1597

- Fixed error where requested songs would not play during scheduled playlist blocks. #1620

- Fixed an issue where ports weren't unassigned when cloning a new station. #1524

- Fixed various bugs and improve overall performance when processing large collections of new media. #1450

- Fixed browser errors that prevented viewing certain larger log files. #1639

- Fixed the displayed time zones in several station reports to match the station's time zone as set in the database,
  rather than UTC or local time.

---

# AzuraCast 0.9.5 (May 14, 2019)

Work on AzuraCast never stops, and with the project increasing in popularity, we've been even busier than before. A lot
of new improvements have rolled out over the last month, along with a ton of bug fixes. This point release includes:

## New Features

- **Theme Improvements**: We've made some changes to our theme to make it more intuitive, easier to use and more
  accessible for mobile phone users. Buttons are bigger and clearer, forms are organized in a more concise way, and the
  station's profile page now gives you direct access to important functions.

- **Schedule Overhaul**: Previously, each user had their own time zone on their profile, and the system had a default
  time zone setting, but schedules were based on UTC and constantly had to be converted back and forth from the user's
  local time. This conversion caused a number of problems. To address them, we completely restructured the time zone
  system. Now, each _station_ has its own time zone, and all scheduled playlists are based on this time zone. Liquidsoap
  and Icecast/Shoutcast are also run in this time zone, so schedule times (and logs) will always be consistent.

- **API Parity**: We've done a _lot_ of work to make all of the core functionality of AzuraCast available via our REST
  API, and we're pleased to announce that as of this release, all major functions are possible entirely via API calls,
  both for global administration and per-station management functions. As a reminder, you can
  visit `your-azuracast-url/api` for API documentation specific to your installed version that you can test in-browser
  against your own installation.

- **A Prettier Public Player**: Our public player has been rewritten as a standalone Vue component (so you can use it
  for your own custom players, too), and it got a big design update as part of that process. The biggest new feature is
  the ability to switch between available mount points and remote streams. The player is also now much more resilient to
  disconnection and will intelligently reconnect after a few seconds.

- Station cloning has been rebuilt from the ground up to fix a number of issues and to improve its performance with
  large music libraries.

- The "Reorder Playlist" page now includes buttons to manually move playlist items up and down, making the page
  accessible for those using screen readers.

## Bug Fixes

- Fixed #1382, #1402 and other bugs relating to "don't loop", "play only once" and other special playlist types.

- Per #1400, the Streamers/DJs page will not let you use certain characters in your DJ passwords that are known to cause
  problems with Liquidsoap authentication.

- Fixed #1405 and other bugs related to the new Liquidsoap AutoDJ process that prevented AzuraCast from getting proper "
  rich metadata" about the currently playing song (i.e. duration, source playlist, etc).

- Fixed #1499 and #1459, bug reports relating to the default settings used for AAC and OPUS streams in Liquidsoap. We
  also added the `libsamplerate` library to Liquidsoap, which greatly improves conversion from one sample rate to
  another (as is often necessary with OPUS streams).

## Other Notes

- As of this release, we are no longer supporting new installations via the Ansible ("Traditional" or "Bare-metal")
  installation type. These installation types have represented a disproportionate amount of support issues, and have put
  a very heavy toll on our volunteer support team, so we have removed the Ansible installation instructions from our
  homepage completely. We will continue to distribute updates to existing users, and we will make our best efforts to
  continue feature parity between both installation types.

- You can now set playlist schedule times on a per-minute basis via HTML5 `<input type="time">` fields, instead of the
  15-minute increment dropdowns.

- The "Once per Day" playlist schedule type has been merged into the main "Scheduled" type. Just set the start and end
  times to be the same to achieve the same effect. Existing playlists were converted to this format automatically.

- If you have "Normalization and Compression" turned on, this will now use a slightly different function inside
  Liquidsoap, which produces a cleaner, crisper audio signal.

- If AzuraCast detects that you've modified your installation locally (specifically your Ansible/"Traditional"
  installation), error reports won't be sent to our Sentry service even if it's turned on. We were getting a huge number
  of error reports about code we didn't write!

---

# AzuraCast 0.9.4.2 (Mar 31, 2019)

This is another incremental bug-fix release to resolve some outstanding issues with our transition back to using
Liquidsoap to drive the majority of the AutoDJ functionality.

## Bug Fixes

- When restarting a station, the AzuraCast error message won't play immediately, but it will play if your station has
  nothing to play.
- Cue settings provided for files (custom start and end times for the file) are now honored by Liquidsoap again.
- If you make a change to a playlist that should prompt a restart of your station, it will now properly do so.
- The Song Listener Impact report now works properly again.

---

# AzuraCast 0.9.4.1 (Mar 27, 2019)

This release is a very minor release intended to fix a bug identified in 0.9.4 with the initial setup script.

This release also incorporates several playlist changes. As a result of these changes, "Manual AutoDJ" and the regular
AzuraCast AutoDJ mode have been merged. Liquidsoap handles the scheduling and playback of all "once every x" and
otherwise scheduled playlists, and AzuraCast's AutoDJ handles, by default, the "general rotation" playlists. If an error
occurs with AzuraCast's AutoDJ, Liquidsoap will manage general rotation playlists, too.

By having Liquidsoap manage playlists directly, we can now introduce two new settings that weren't possible before:

- You can now have a playlist "interrupt" whatever's playing on the radio; for example, if you have a playlist set to
  play at exactly 4:00PM, it will stop whatever is playing to play at exactly that time.
- You can set playlists to only loop once, so even if they would play multiple times in their scheduled time block,
  they'll only play once before Liquidsoap resumes other programming.

We have also made improvements to how Liquidsoap is notified of playlist changes from AzuraCast, so these notifications
should be instant as soon as playlists are changed in the web interface.

These features are all rather new and are still in testing, so please report any issues you find with them to us via the
GitHub issues page.

---

# AzuraCast 0.9.4 (Mar 25, 2019)

This incremental release features major improvements to our public and embedded players, as well as a number of new
features and bug fixes.

## New Features

- **"Jingle Mode" Playlists**: You can now tell AzuraCast to ignore metadata updates for a given playlist, so that when
  it is played, listeners will hear the audio but won't see the song's title or artist metadata in their player. This is
  especially useful for playlists that contain jingles or advertisements.

- **User-Selectable Mount Points/Relays**: You can now provide display names for each of your mount points and remote
  relays, then choose which of them are visible on public pages.

- **Updated Public Player**: We've made significant updates to our public player interface, which now has a live
  playback progress bar, always-visible player controls, and the ability to select from multiple mount points/relays (
  see above).

- **Support for Two-Factor Authentication**: Now any user on AzuraCast can significantly improve their security by
  enabling two-factor authentication when logging in. Our two-factor solution is powered by TOTP one-time passwords and
  are convenient and secure: just scan the provided QR code with an app on your smartphone (FreeOTP, Authy, or any other
  TOTP app) and it will generate a unique code every few seconds. You will be asked for this code any time you log in.
  If you lose access to your authenticator at any time, you can follow
  the [password reset instructions](https://github.com/AzuraCast/AzuraCast/blob/main/SUPPORT.md#reset-an-account-password)
  to recover your account.

- **Automatically Send Error Reports**: Thanks to our friends at [Sentry](https://sentry.io/), we've added the ability
  to automatically send error reports to our team for review. This feature is disabled by default and is opt-in from the
  System Settings page, and the error reports we receive are anonymized. These error reports can help us diagnose and
  resolve problems significantly faster.

- The Twitter web hook now has support for rate limiting, so you can tell AzuraCast to only send one Tweet every few
  seconds, minutes or hours.

- You can now switch your theme from light to dark from anywhere in the application. We've also added a new "Help" page
  from the dropdown menu that links to our support documentation to help users find common solutions to their problems.

## Updates and Bug Fixes

- The installation method formerly known as "Traditional" has been renamed to "Ubuntu Bare-Metal" installation. The
  term "traditional" conveyed to many people that this was the "normal" or preferred way of installing AzuraCast, when
  this is not the case; we heavily recommend the Docker installation method unless you have a specific reason not to use
  it.

- Liquidsoap on Ubuntu bare-metal installations has been updated to version 1.3.6, offering performance and bug fixes.

- The changes made to playlist weighting in version 0.9.3 have been reverted to the weighting algorithm used in previous
  versions.

- Further improvements have been made to the process of notifying AzuraCast of song changes, and the station profile
  will now update much quicker to reflect song changes.

- A number of "yes/no" radio buttons across the application have been replaced with more intuitive single toggle
  checkboxes.

- Pagination controls on the Media manager have been fixed.

- The playlist form has been divided into tabs for easier navigation, similar to the station form.

---

# AzuraCast 0.9.3 (Feb 26, 2019)

As we start to improve our project stability and more users prefer to use packaged release builds of our software, we
are working to produce more frequent incremental releases. This is one such release, primarily consisting of bug fixes
and minor under-the-hood improvements over the previous version. All users are encouraged to upgrade.

## Updates and Bug Fixes

- Media uploads have been reworked to process in "batches", improving both performance and reliability of the upload
  process.

- Several new protections have been introduced to prevent users from deleting themselves, removing critical permissions
  from their own accounts, or modifying the "super administrator" permission. You can also re-establish any account that
  previously lost said permissions by
  running `[your cli command] azuracast:account:set-administrator [your-email-address]`.

- The AutoDJ's weighting tool will factor in the total size of each playlist. If you have one playlist with 5 songs and
  another with 500, this should greatly prevent the tendency to hear the 5-song playlist far more often than you would
  otherwise want.

- The AutoDJ will consider the weighting of all types of playlists now, not just general rotation ones, so if you have
  multiple scheduled playlists on top of one another, it will use weighting to determine which playlist to play from.

- The `station_watcher` secondary process has been removed on Docker installations, replaced by new scripting
  improvements to our Liquidsoap integration. This allows users of both Docker and Traditional installations to enjoy
  support for AzuraCast's rich metadata even when using "Manual AutoDJ" mode or "Advanced" playlists.

- Generation of navigation sidebars (both for administration and per-station management) is now managed by the Event
  Dispatcher, and can be extended by plugins that wish to add new menu items.

- Starting with this release, releases are now tracked on the Composer package
  manager's [Packagist repository](https://packagist.org/packages/azuracast/azuracast). We will be using Packagist to
  handle Docker installations and updates, improving maintainability and significantly reducing the overall size of
  Docker containers further.

---

# AzuraCast 0.9.2 (Feb 7, 2019)

This minor version release includes a number of bug fixes, performance improvements and smaller new features, and is an
incremental update on the roadmap to our version 1.0 release.

## Major Updates

- **WebDJ, Stream Live from your Browser**: Thanks to a special feature of our AutoDJ tool Liquidsoap, you can stream
  directly to your station from your web browser without installing any other software! If your station has both the "
  Enable DJs/Streamers" and "Enable Public Page" settings turned on, you will see a link for the "Web DJ" on the station
  sidebar. Note that some browsers only allow this feature to work on HTTPS pages, so make sure you're using a secure
  connection!

- **New Unified Docker Container**: Optimizations of our Docker infrastructure have allowed us to consolidate the role
  of 5 of our previous Docker containers into a single unified "Web" container. If you're using Docker, you should
  update your `docker-compose.yml` file when prompted by the updater script to take advantage of this new
  infrastructure. After updating, you will need to re-run any LetsEncrypt connection scripts, but once you're set up,
  IceCast will also be able to take advantage of your LetsEncrypt certificates and certificates will automatically be
  renewed inside the Docker container.

- **Redesigned Station Profiles and Per-Station File Quotas**: The form you see when editing your station profile has
  been divided into new tabs for easier use. If you are a global administrator of a multi-tenant AzuraCast instance, you
  will be able to modify the station's media directory, enabled/disabled status and file upload quota. This quota will
  be shown to station managers on the "Music Files" page, and is automatically enforced any time a user uploads music
  from the web interface.

- **Release-Only Updates**: If you prefer to update less frequently, you can now choose to only update to the latest
  tagged release of AzuraCast instead of using the typical "rolling release" schedule. Both `./update.sh --release`
  and `./docker.sh update --release` will update their respective installations only if a new tagged version has been
  released.

## Minor Updates/Technical Notes

- Several new API endpoints have been created, allowing you to use the REST API to manage system settings, branding,
  users and permissions.

- When viewing your API documentation (`your-azuracast-site/api`), the documentation will allow you to test the
  endpoints against your own installation instead of the demo instance.

- A new log viewer page has been created for easier diagnosis of issues. Individual station managers can view logs
  specific to their stations, and global administrators can see all logs across the installation.

- Album art has been moved to the filesystem, resulting in significantly lighter database sizes.

- Under the hood, Flysystem is now being used to access and cache metadata about uploaded station media. This will allow
  us to expand to support S3 buckets and other remote locations for station media uploads in the future.

- In some previous versions, AzuraCast referred to a third-party CDN (CloudFlare's CDNJS) for many of its static assets
  for performance reasons. From this version forward, those assets have been moved back to the AzuraCast instance
  itself. This helps to preserve the self-contained nature of AzuraCast and avoid unnecessary downtime caused by
  third-party services.

- You can now install another version of Shoutcast even if a different version is already installed.

## Bug Fixes

- Improvements to log handling and rotation have been made across the system. These changes should significantly reduce
  the tendency of log files to grow to fill the host filesystem.

- Differences in the handling of remote URLs and sequential playlists between the AutoDJ's normal and "Manual AutoDJ"
  modes have been reconciled.

- Both frontend and backend dependencies have been updated repeatedly to address security and stability fixes.

- An issue causing a portion of the site to be inaccessible to those using screen readers has been resolved, and other
  improvements to accessibility have been made across the system.

---

# AzuraCast 0.9.1 (Nov 18, 2018)

This minor version release includes a number of bug fixes, performance improvements and smaller new features, and is an
incremental update on the roadmap to our version 1.0 release.

## Major Updates

- **WebDJ, Stream Live from your Browser**: Thanks to a special feature of our AutoDJ tool Liquidsoap, you can stream
  directly to your station from your web browser without installing any other software! If your station has both the "
  Enable DJs/Streamers" and "Enable Public Page" settings turned on, you will see a link for the "Web DJ" on the station
  sidebar. Note that some browsers only allow this feature to work on HTTPS pages, so make sure you're using a secure
  connection!

- **New Unified Docker Container**: Optimizations of our Docker infrastructure have allowed us to consolidate the role
  of 5 of our previous Docker containers into a single unified "Web" container. If you're using Docker, you should
  update your `docker-compose.yml` file when prompted by the updater script to take advantage of this new
  infrastructure. After updating, you will need to re-run any LetsEncrypt connection scripts, but once you're set up,
  IceCast will also be able to take advantage of your LetsEncrypt certificates and certificates will automatically be
  renewed inside the Docker container.

- **Redesigned Station Profiles and Per-Station File Quotas**: The form you see when editing your station profile has
  been divided into new tabs for easier use. If you are a global administrator of a multi-tenant AzuraCast instance, you
  will be able to modify the station's media directory, enabled/disabled status and file upload quota. This quota will
  be shown to station managers on the "Music Files" page, and is automatically enforced any time a user uploads music
  from the web interface.

- **Release-Only Updates**: If you prefer to update less frequently, you can now choose to only update to the latest
  tagged release of AzuraCast instead of using the typical "rolling release" schedule. Both `./update.sh --release`
  and `./docker.sh update --release` will update their respective installations only if a new tagged version has been
  released.

## Minor Updates/Technical Notes

- Several new API endpoints have been created, allowing you to use the REST API to manage system settings, branding,
  users and permissions.

- When viewing your API documentation (`your-azuracast-site/api`), the documentation will allow you to test the
  endpoints against your own installation instead of the demo instance.

- A new log viewer page has been created for easier diagnosis of issues. Individual station managers can view logs
  specific to their stations, and global administrators can see all logs across the installation.

- Album art has been moved to the filesystem, resulting in significantly lighter database sizes.

- Under the hood, Flysystem is now being used to access and cache metadata about uploaded station media. This will allow
  us to expand to support S3 buckets and other remote locations for station media uploads in the future.

- In some previous versions, AzuraCast referred to a third-party CDN (CloudFlare's CDNJS) for many of its static assets
  for performance reasons. From this version forward, those assets have been moved back to the AzuraCast instance
  itself. This helps to preserve the self-contained nature of AzuraCast and avoid unnecessary downtime caused by
  third-party services.

- You can now install another version of Shoutcast even if a different version is already installed.

## Bug Fixes

- Improvements to log handling and rotation have been made across the system. These changes should significantly reduce
  the tendency of log files to grow to fill the host filesystem.

- Differences in the handling of remote URLs and sequential playlists between the AutoDJ's normal and "Manual AutoDJ"
  modes have been reconciled.

- Both frontend and backend dependencies have been updated repeatedly to address security and stability fixes.

- An issue causing a portion of the site to be inaccessible to those using screen readers has been resolved, and other
  improvements to accessibility have been made across the system.

---

# AzuraCast 0.9.0 (Oct 8, 2018)

**Important note:** AzuraCast is a "rolling-release" product that is updated almost daily. If you're running AzuraCast,
you're strongly encouraged to update as frequently as possible to get the latest features, bug fixes and security
patches. Release numbers are only indicated to chronicle updates over time and give a general idea of an installation's
update status.

## Major Updates

- **Shoutcast 2 DNAS is no longer bundled with AzuraCast.** While Shoutcast has been a popular offering that was bundled
  with AzuraCast after significant demand, it has always been non-free, proprietary software, the only component in the
  AzuraCast stack that is not free and open-source. With the release of Shoutcast's
  new ["freemium" pricing structure](https://www.shoutcast.com/Pricing) has also come a new, more aggressive license
  associated with distribution of the software. As such, AzuraCast can no longer bundle Shoutcast 2 DNAS with new
  installations, and we strongly recommend that any stations that can use Icecast do so. We do still support the
  software, however, and you can manually install it by uploading the `.tar.gz` file provided by Shoutcast into a new
  page in the system administration.

- **Our support for Icecast is now even better suited for commercial radio stations.** Along with our withdrawal of
  out-of-the-box support for Shoutcast, we've been working hard to make improvements to our Icecast integration so it
  can better serve commercial radio stations that depend on reliable, accurate reporting. If you're using the latest
  version of AzuraCast, you will now see much more accurate information on your listeners, especially if you're using
  the Docker installation or operating behind CloudFlare protection.

- **Sequential Playlists are now supported.** You can now create playlists that must play in a specific order. You can
  reorder the contents of these playlists at any time from a station's "Playlists" page by clicking the "Reorder"
  button.

- **Custom metadata can be assigned to any media.** System administrators can define new "custom metadata" fields that
  will appear when editing any media across the system. These custom fields are selectable when viewing the "Music
  Files" page and are returned in Now Playing API calls.

- **The station's Profile is now its "home" page.** As we've expanded the Profile page, and as we continue to add new
  features in the future, this page has quickly become the de-facto main page for overseeing every aspect of a running
  station. To reflect this, when you click "Manage" next to a station, you'll now immediately be taken there. You can
  still access the previous home page via "Reports" > "Statistics overview".

- **The core "Now Playing" library is a standalone PHP library now.** We're always looking for new ways to give back to
  the open-source software community. Now, along with [AzuraForms](https://github.com/AzuraCast/azuraforms), we have
  spun off our [NowPlaying](https://github.com/AzuraCast/nowplaying) library to be a standalone component you can
  include in your own PHP code. It's very useful for abstracting out the differences between Icecast, Shoutcast 1 and
  Shoutcast 2 sources into a single return format.

- **AzuraCast now has early support for plugins.** Many of you have wanted to customize the internal workings of
  AzuraCast without needing to fork the main codebase and maintain your own copy. You can now do this with the help of
  our new plugin architecture, which lets you hook directly into the most important events that happen "under the hood"
  in AzuraCast without modifying the core code. Check out
  our [example plugin](https://github.com/AzuraCast/example-plugin) for a demonstration of what plugins can do.

- **You can now broadcast locally and remotely using the same station.** We have separated out remote relays from the
  local broadcast, so now instead of having to choose between one or the other, you can both broadcast locally and
  stream to remote relays using the same station. Any existing remote setup is automatically migrated to this new system
  when updating AzuraCast.

- **Google APIs are no longer used in the system.** In order to help ensure the privacy of our station operators, you no
  longer need to use Google Maps or supply a Google API key to take advantage of the live listeners report.

- **AzuraCast has a new homepage!** Visit our simple yet informative new homepage, powered by VuePress,
  at [azuracast.com](https://www.azuracast.com/).

## Minor Items and Bug Fixes

- Album artwork will now be properly cached by your browser and intermediate services like CloudFlare.

- Responses from the `/api/nowplaying` endpoint will now properly respect the "Force HTTPS" and "Prefer Browser URL"
  settings, updating URLs to reflect those changes when visited from different hosts or secure pages.

- When editing a station's profile, AzuraCast will automatically check if a port is in use by another station before
  letting you save changes. This should prevent a very common class of error from ever happening.

- All time zones are selectable, not just one time zone per hour offset, so you can properly select the time zone that
  reflects your DST settings.

- You can now customize both how long you want to keep song history in the database overall (for reporting) and how many
  song history items you want to return in the Now Playing API.

- Stations will no longer automatically be assigned to the very popular port 8080, as it's often in use by other
  processes on the same servers.

- Support for Telegram has been added to web hooks.

---

# AzuraCast 0.8.0 (Dec 15, 2017)

This release includes the cumulative development work done over the fall and winter of 2017 on the AzuraCast project. It
includes a number of minor fixes and some small but significant improvements to the system, the user experience, and the
security of the application.

## Major Items

- **Album Art support**: Album art is automatically loaded from uploaded music, and can be updated at any time from the
  media manager. The images are displayed using cache-friendly URLs. All APIs that return "now playing" data now also
  include album artwork, if available.
- **Broadcast to remote streams:** Running your AutoDJ from AzuraCast but broadcasting to a hosted radio server
  elsewhere? Now you can directly connect to a remote server for both statistics and broadcasting by visiting the "Mount
  Points" section of the station manager.

## Minor Enhancements

- **Specify Station "Short URLs"**: You can now customize a station's "stub" URL, as is used in both API calls and the
  public-facing listener page, independent of the station's name.
- **More Base URL options**: You can choose to prefer to use the current URL the visitor is using in favor of the base
  URL (i.e. if you have multiple domains that point to one instance, and want the URLs to use whichever one the viewer
  is looking at). You can also specify if all URLs should force HTTPS.
- **Support for OGG Opus**: You can now add a mount point that specifically broadcasts music using the OGG Opus codec.
  This is particularly useful if you are building a bot for a voice service like Discord. (Note: due to a known issue,
  metadata does not transmit properly via Opus, so always make sure to have at least one non-Opus mountpoint running for
  a station to continue seeing statistics.)
- **Better accessibility**: Thanks to the help of project contributors, the web interface has been optimized for full
  accessibility when viewed with screen readers or other assistive technology.
- **Hide a station**: You can now hide a station from public view (both from showing a public page and appearing in
  public-facing APIs) if you intend to use it purely internally or aren't ready to launch it yet.
- **Faster updates to Now Playing data**: Across the system, a new websocket-based update system (powered by nginx and
  nchan) has been implemented to support live updates as now-playing data changes. Now, song and listener count changes
  will be reflected immediately, making listener pages far more responsive and accurate.

## Infrastructure

- **Update to PHP 7.2**: Both Docker and Traditional installs have been updated to the newest stable release of PHP,
  version 7.2.
- **Strengthened Password Security**: The existing bcrypt-based password hashing mechanism is robust and secure, but
  with the update to PHP 7.2, a newer, even more secure method of storing passwords has been made available via Argon2i.
  The web app will automatically rehash passwords to the newer, stronger format upon the user's first login.
- **Frontend dependencies relocated to CDNs**: Many of the frontend dependencies used by the web app have been moved and
  now refer to CDNJS and other CDN sites, so once they've loaded in your system they will perform better. This has also
  significantly lightened the size of the AzuraCast codebase.
- **Updated LiquidSoap to 1.3.3**
- **Updated IceCast-KH to 2.4.0-KH7**
- **Updated Shoutcast DNAS to latest version**

## Bug Fixes

- Traditional install updates will no longer run all install items unnecessarily.
- You can no longer create two users with the same e-mail address.
- If you're in a locale with 24-hour time, the time display will reflect this correctly.
- You can now use relative paths in Docker backup/restore commands.
- Background media processing has been optimized to use significantly less memory.
- After making changes on the media manager, you will no longer be taken back to the first page after every reload.
- UTF-8 in song titles is handled properly across the system, and proper titles will be displayed everywhere.

---

# Earlier Versions

The individual changes in versions before this were not tracked.
