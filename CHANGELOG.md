# Rolling Release Changes

These changes have not yet been incorporated into a stable release, but if you are on the latest version of the rolling release channel, you can take advantage of these new features and fixes.

This release includes many contributions from members of our community as part of the annual Hacktoberfest event, where we selected a number of items that our core developer team, along with the community submitting pull requests, could work on during the month. 

## New Features/Changes

- **Media storage overhaul**: The way media is stored and managed has been completely changed:
  - Station media, live recordings, and backups have "Storage Locations" that you can manage via System Administration.
  - Storage locations can either be local to the server or using a remote storage location that uses the Amazon S3 protocol (S3, DigitalOcean Spaces, Wasabi, etc)
  - Existing stations have automatically been migrated to Storage Locations.
  - If more than one station shares a storage location, media is only processed once for all of the stations, instead of being processed separately.

- Statistics now include the total _unique_ listeners for a given station in a given day. On the dashboard, you can switch from the average listener statistics to the unique listener totals from a new tab selector above the charts.

- There is a new, much friendlier animation that displays when Docker installations of AzuraCast are waiting for their dependent services to be fully ready. This avoids showing the previous messages, which often looked like errors, even though they weren't.

- The global "Administer Users" and "Administer Permissions" permissions have been *removed* from the system. In both cases, users who had this permission could effectively make themselves a super administrator user, so user and permission management is now only accessible by users with the "Administer All" permission. 

## Code Quality/Technical Changes

- **Removal of InfluxDB**: Despite the ever-increasing complexity of AzuraCast, sometimes we review our dependencies to ensure if the additional load and maintenance overhead they cause is worth the benefit to our system. In the case of our InfluxDB time series database, we decided that this dependency was no longer needed, and its purposes could be fully handled by our existing MariaDB database. This decision was also motivated by Influxdata releasing a non-backwards-compatible new version of the software, which would present a very challenging migration to our users. Instead, we have removed the dependency entirely. 

- **Multiple code quality improvements**: We have incorporated stricter code standards checking into our continuous integration (CI) process and updated our code accordingly, so any code that publishes to stations will meet a stringent set of code style, standards, static analysis and pass our normal test suites.

- **Songs table overhaul**: A `Songs` table has existed for the entirety of AzuraCast's existence as the authoritative source of spelling and capitalization for all song titles, artists, etc. This solution was not scalable to large stations and there was no effective way to clean up this table once it became vastly oversized. This update removes the `Songs` table entirely and relocates its attributes to the various tables that used it before (song history, request queue, etc). 

- **File Operations API Changes**: In order to clarify the meaning of parameters, the `file` parameter for the following API endpoints has been changed to `current_directory`:
  - `/api/station/{id}/files/list`
  - `/api/station/{id}/files/directories`
  - `/api/station/{id}/files/batch`
  - `/api/station/{id}/files/mkdir`
  - `/api/station/{id}/files/upload`

- SweetAlert has been updated to SweetAlert2; alert prompts will now also have the same theme as the current active theme on the page.

- Update checks have been simplified; if you are on the "rolling release" channel you will see rolling release updates, and if you are on the stable channel you will see stable version releases only.

- When adding tracks to, or removing tracks from, a playlist, its current playback queue will be more intelligently updated instead of being reset completely, which was much more likely to lead to duplicate artist/title playback.

- The SASS (specifically, SCSS) code of our underlying theme has been greatly simplified and updated to include some features from the Bootstrap 4.5 library.

- The station profile and public player are now fully converted into Vue components, which improves their performance for many users and allows us to much more easily make changes to them in the future, including making the public player customizable by users embedding it into their own websites. 

- Plugins can now add their own ACL permissions via the new `App\Event\BuildPermission` event.

## Bug Fixes

- If the current stable release uses a different `docker-compose.yml` file than the current rolling release, the `docker.sh` utility script will accommodate for this. Make sure to run `./docker.sh update-self` before any updates to ensure the script is up-to-date. 

- The "Move" batch command in the media manager has been fixed to work as expected; if you select to move a folder to another one, it will move the entire folder instead of just moving its contents over.

- Several optimizations have been made to how media is processed (and what media isn't processed by the system), which should improve performance and avoid repeatedly processing files that can't be processed.

- Exceptions thrown when processing items in the Message Queue are now logged appropriately.

- When importing a playlist from a PLS/M3U file, the order of the imported files will be preserved in the playlist.

---

# AzuraCast 0.10.4 (Oct 1, 2020)

This release is being tagged to incorporate all of the feature improvements, bug fixes and other changes we've made to AzuraCast over the last few months into a stable release just prior to our Hacktoberfest event. We expect the Hacktoberfest-related changes to be significant and to require a significant amount of testing, so we wanted to ship a stable version before making these major changes.

## New Features/Updates

 - Significant work has been done to rewrite the core components of the AutoDJ, scheduler, and message queue dispatcher. This should greatly improve the reliability and accuracy of our track playback among other improvements.
 - Raspberry Pi support has been restored, and AzuraCast's Ansible installation is once again compatible with Raspberry Pi 3B and 4 devices. (#3048, #3028)
 -  Added a "not backed up recently" notification to the application dashboard and a reminder to back up during the update process. (#2756)
 - You can now only enable the "Force HTTPS" setting when visiting the page from an HTTPS connection, preventing you from immediately locking yourself out. (#2932)
 - You can now append `?theme=dark` or `?theme=light` to the query string of any public page to change its theme, regardless of your branding settings or user preferences.
 - Media Manager performance has been improved if you have large subfolders with many files in them.

## Bug Fixes/Other Changes

 - Minor security fix: If you are a user with the global "Administer Users" permission but not the global "Administer All" permission, you will no longer be able to impersonate a user with higher permissions than yourself, thus granting you those permissions. (#3097)
 - Fixed a bug where you could continue into AzuraCast without finishing setup. (#2958)
 - Icecast now uses the same SSL certificate your web connection uses via LetsEncrypt setup. (#2969)
 - Connecting to a SHOUTcast 1 remote relay works again. (#2989)
  - The "Play" icon will now properly switch between playing and not-playing states for only the actively playing item. (#3170)
  
---

# AzuraCast 0.10.3 (Jun 21, 2020)

## New Features and Infrastructure Changes

- **On-Demand Streaming/Download**: You can now allow on-demand streaming of certain playlists, which will allow your audience to listen to and download tracks from the specified playlists at any time. First, enable on-demand broadcasts on the station's profile, then enable it on every playlist that contains tracks you want to be available for on-demand download. Keep in mind any copyright restrictions that may apply in your area.

- The visual cue editor for media has been reworked from the ground up and now uses a server-side service to generate the waveform graphics for each track where possible. This improves the stability and reliability of this tool greatly.

- We have merged the configuration from our "multi-site" setup into our core Docker Compose configuration. This means that every Docker installation with an up-to-date `docker-compose.yaml` file has an nginx reverse proxy included, which allows for easy automated renewal of LetsEncrypt certificates and support for multiple Docker containers serving multiple domains alongside your AzuraCast installation. See issue #2855 for instructions on migrating your existing SSL certificates into this new infrastructure.

- Static assets (Vue components, scripts loaded from NPM, etc) are now built automatically with each new commit so they will be up-to-date. For most users, this won't be a noticeable change, but is important to note for developers. On Docker, this build happens as part of our GitHub action and is included in the image you pull when updating; on Ansible installations, your server itself builds the static assets.

- After working with the Liquidsoap team, we have moved "Remote Stream" playlist types to be exclusively handled by Liquidsoap, which should improve their functionality.

- There is a new CLI command to list all accounts on an AzuraCast installation. For Docker users, this can be invoked via `./docker.sh cli azuracast:account:list`.

## Bug Fixes/Other Changes

- The "base URL" on the settings pane now includes the URL scheme (`http://` or `https://`) so you can prefer one or the other without forcing HTTPS for the entire web application. #2814

- Icecast now has the proper "hostname" value set. #2839

- Fixed a minor issue with the TuneIn web hook not sending album art. #2863

- Fixed an error causing UNIX timestamps to appear on certain tables instead of formatted times. #2866

- Fixed a number of areas that had issues with translated strings. #2945 #2946 #2957

---

# AzuraCast 0.10.2 (May 6, 2020)

This is a primarily bug fix oriented release that includes several important improvements. All users of AzuraCast are encouraged to update to this version, especially users affected by the issues listed below.

## Upstream Dependencies

- Liquidsoap has been updated to version 1.4.2 on both Ansible and Docker installations, fixing a critical bug that caused stations with numerous playlists to fail to be able to start.

- Icecast has been updated to use the latest release from Karl Heyes' "KH" branch, 2.4.0-KH14.

## New Features

- You can set a default album art URL on a per-station level; if one is not set at the station level, the system-wide one will be used instead (and if one is not provided there, the default AzuraCast album art image will be used instead).

- A new API endpoint has been created that will allow you to update the current metadata being played programmatically. The endpoint can be accessed via `/api/station/(station_id)/nowplaying/update` and requires an API key with the "Manage Station Broadcasting" permission. A future version will further document the capabilities of this endpoint, but it is already available for initial use in this version.

- The AzuraCast homepage will now remind you if you haven't run any sort of backup (either manual or automated nightly) in at least the last two weeks. (#2756)

## AutoDJ Changes/Fixes

- The AutoDJ playlist weighting algorithm has been slightly tweaked to include lower-weighted playlists more frequently.

- The system debugger page now has a "Rebuild Queue" button that will erase the current upcoming song queue and build a new one, while providing full debugging information on the screen. If you are having issues with your scheduled playlists not playing, you should use this function to help determine how the AutoDJ is running.

- The "Cued" time in the upcoming song queue will now accurately reflect when AzuraCast expects the track to actually be played.

- Requests will be added into the queue at the appropriate time. (#2772)

---

# AzuraCast 0.10.1 (Apr 26, 2020)

## Bug Fixes

 - This very minor release fixes an issue caused by jQuery 3.5.0 that prevented dropdowns from working correctly.
 
---

# AzuraCast 0.10.0 (Apr 26, 2020)

AzuraCast 0.10.0 is an incremental release with several bug fixes and improvements across the application. This version introduces minor changes to the AzuraCast API (see below) and is tagged as a new minor release as a result.

## New Features

- **Ubuntu 20.04 LTS Support**: The latest long-term-support (LTS) version of the Ubuntu linux distribution was released this week, and we're already updating the various components of our application to support it. Our primary Docker images already run on Ubuntu 20.04 and our Ansible installation supports 20.04 for new installations. If you're starting a new station, using Ubuntu 20.04 is strongly recommended.

- AzuraCast's AutoDJ will now build a queue of songs ahead of the current song. Instead of just seeing the "next" song, you can view the entire upcoming queue (via `Reports` > `Upcoming Song Queue`) and remove items from it. The length of the queue is customizable on a per-station level.

- A default minimum request threshold of 15 seconds has been added to prevent users from flooding your request infrastructure even if you have no threshold set. You can also now clear all pending requests via a single button click in the web interface.

- You can manually trigger the reshuffling of "Shuffled" playlists via the "More" dropdown menu next to the playlist in the web UI.

## Bug Fixes/Other Changes

- The "Edit Liquidsoap Configuration" page has been restricted to users with the "Manage Station Broadcasting" permission instead of all station managers. Be aware that users with this permission can not only control the status of their own station's frontend/backend services, but via editing custom Liquidsoap configuration can also possibly impact other stations on the same installation.

- Several improvements have been made to scheduling and the AzuraCast AutoDJ in general (#2740, #2689, #2631). This is an ongoing work in progress; if you continue to encounter issues with your installation not properly adhering to scheduling, please see super-issue #2631.

- API responses are now strictly typed and will more reliably conform to these type requirements. This makes API responses more useful if you're using or importing them into strictly typed languages. A majority of the API responses haven't fundamentally changed with the exception of having stricter types.

- Fixed a bug where non-public stations would trigger the Now Playing API response returning in a non-standard format. (#2709)

- Metadata from previous DJs won't be played back when the next DJ connects. (#2728)

- Actions that will disconnect listeners (i.e. Restart Broadcasting) now have a modal confirmation dialog.

- Songs that contain the pipe (`|`) character won't cause errors with Liquidsoap. (#2631)

---

# AzuraCast 0.9.9 (Mar 14, 2020)

## New Features and Important Changes

- **Auto-Assign Folders to Playlists**: If you select a folder in the Music Files manager and add it to one or more playlists, any songs uploaded inside that folder (via the web interface or SFTP) will automatically be added to that playlist. If the files in the folder are also in other playlists, they won't be removed from those playlists, just added to the folder's playlists.

- **Streamer Schedules**: You can now set scheduled times when streamers are supposed to be broadcasting. You can choose to "enforce" this schedule, which means that the streamer/DJ can only connect during their scheduled time and no other times.

- **Schedule View**: Now that both playlists and streamers are scheduled, you can use AzuraCast to produce a full schedule lineup for your station including both upcoming scheduled playlists and scheduled DJ/streamers. We have created new schedule API endpoints to facilitate this, and added a new "Schedule" section to station profiles.

- **Record Live Broadcasts**: Thanks to several improvements within AzuraCast, we now track the start and end times for every DJ/streamer broadcast. You can view the full history for each streamer by clicking the "Broadcasts" button next to their name. We also now offer the ability to automatically record live broadcasts; the recording of each given broadcast can be viewed in the same "Broadcasts" panel. Also, when viewing the song playback timeline, you will be able to see which DJ/streamer was live when a particular track was broadcast.

- **Advanced Liquidsoap Configuration Editor**: We've replaced the single "Advanced Custom Configuration" field in the station profile with a newer, much more powerful editor tool for directly editing Liquidsoap configuration. This editor shows the automatically generated configuration alongside your custom code, and allows you to inject custom code in multiple new places.

- **Visual Cue Point Editor**: Thanks to a pull requested contribution from Bjarn Bronsveld, you can now edit a music file's cue-in/cue-out/fade-in/fade-out points with a rich, interactive waveform viewer that shows markers for each of the specified points. This offers a significantly improved experience over the previous timestamp input fields (which can still be manually edited if necessary).

- **New IP Geolocation Options**: With the changes to the MaxMind GeoLite database, we've abstracted out how we perform IP geolocation. By default, all Docker installations come with the DBIP lookup tool, which is suitable for a majority of use cases. If you prefer GeoLite for its result accuracy, you can enter a license key acquired from their web site and AzuraCast will download and use the GeoLite database instead.

## Bug Fixes and Minor Improvements

- The `azuracast_web_v2` Docker image is now directly tied to the main code repository, so rolling-release updates are much faster and involve far less load time.

- Multiple playlists can now have the same name (#2281).

- Several bugs with station cloning have been fixed and the feature is now again fully functional. (#2276, #2427)

- The "Prefer Browser URL" and "Enable SFTP Server" settings have been tweaked so they will behave more sensibly, and will not interfere with portions of AzuraCast's web interface that make API calls.

- Several sections of AzuraCast (including, notably, the Duplicate Songs report and Radio Automation code) have been heavily optimized to avoid memory overflow with very large music libraries. (#2003)

- Liquidsoap has been updated to version 1.4.1.

- Quotas are now enforced within the built-in SFTP server. (#2315).

- You can now directly provide an amplification value that Liquidsoap will use on each track from the media manager. (#2334)

- The AzuraCast AutoDJ's scheduling code has been significantly overhauled to be easier to maintain and to fix a number of "edge case" scenarios; you should now see much more accurate track selection that more reliably avoids duplicate artists/tracks while still preferring higher weighted playlists.

- In situations where files are served directly from the filesystem, we now take advantage of nginx's built-in `X-Accel-Redirect` functionality to instruct nginx to serve the file directly from disk instead of passing it through PHP; this results in _much_ faster and more reliable downloads for media previewing, album art, historical broadcasts, backups, and more.

- In newer Vue components (i.e. the media manager, playlist manager, streamer manager) modal dialog boxes will autofocus on the relevant input, and the enter key will submit the modal dialog's form as expected (#2449).

- When previewing a music track or other finite-length file within the AzuraCast web interface, you can seek through the file using a new progress slider alongside the player controls in the header menu.

- You can now make the Listener map fullscreen if desired.

---

# AzuraCast 0.9.8.1 (Jan 11, 2020)

This release includes some infrastructural changes to the application, along with several bug fixes.

## Changes

- **Updated to PHP 7.4:** With the general availability of the latest version of PHP, version 7.4, we wanted to update as soon as possible to take advantage of the performance improvements and new code features available. All installations running this version or later will be on PHP 7.4 or newer.

- **Switched Built-in FTP Service to SFTP:** We encountered a number of issues with our previous built-in FTP service, from trouble with the passive IP range to issues forwarding traffic and some problems with incomplete files being uploaded. These are all resolved with our switch to SFTP, which uses one port (by default, 2022, though this is modifiable by changing/adding `AZURACAST_SFTP_PORT` in `.env` on the host) to handle all incoming connections, is a more modern and secure specification, and supports so-called "atomic" copies, where files are only moved into the destination folder after they are fully uploaded, preventing partial processing errors. The only change from a user experience is that now you must create one or more SFTP-specific accounts for each station rather than using your existing AzuraCast credentials.

- **GeoLite IP Geolocation Database Changes:** The provider of the free GeoLite database, MaxMind, decided to change (with very short notice to users) how they supply the GeoLite IP geolocation databases that we use to add listener location and map coordinates to our Listeners report. This means we can no longer automatically package it with our installations. Instead, if you want to use the Listeners mapping feature, you must visit the new "Install GeoLite Database" page in system administration and supply a license key, available for free from the MaxMind site. We will then automatically download the latest GeoLite database, and keep it updated for you.

- The mini music player inside the AzuraCast interface is now inline with the header menu instead of accessed via a dropdown menu.

- All uncompiled static frontend assets (i.e. Vue code, unminified Javascript, SASS and CSS) have been moved out of the web root and into their own `/frontend` folder. This should have no impact on a vast majority of users, but if you are looking to make custom changes to how the application is styled, be aware that those files are now located somewhere new.

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

- **Updated to Liquidsoap 1.4.0**: Both Ansible and Docker installations have been updated to version 1.4.0 of Liquidsoap. This new version introduces smarter crossfading, better UTF-8 support and a number of quality-of-life improvements. Some of the syntax of the new version is not backwards-compatible with the previous versions, so updating is highly recommended for all users to avoid errors.

- **New Playlist Manager**: Alongside the new Media Manager released in the previous version, we have also rebuilt the Playlist manager to be a fully interactive Vue.js component. This allows for inline editing, reordering, and removal of playlists, and support for the new scheduling features detailed below.

- **Playlist Scheduling Improvements**: All playlist types can now be scheduled, and any single playlist can now have multiple schedule entries. Schedule entries can also have a start and end _date_, for limited-time announcements or seasonal content.

## Bug Fixes/Updates

- Increased the timeout allotted to backups (#2149, #1717) and backup restoration (#2166).
- The built-in Docker FTP service will now use the correct external IP for PASV connections, so it will be compatible with significantly more clients.
- When specifying times for media fade-in/fade-out/cue-in/cue-out, you can specify the time in mm:ss format rather than in total seconds if you prefer (#2117).
- Various accessibility improvements across the system.
- Saving playlists will no longer block the web request, and will be handled instead by a separate worker process, allowing for larger playlists to be saved more frequently (#2068).
- Modified the way we check for the latest CA certificates to avoid stability issues.
- Fixed a bug where clicking breadcrumb navigation in the Media Manager would show all files as "not processed" (#2086).
- Avoid writing to a temporary directory in cases where a song is already locally stored on the server, which should cut down significantly on temporary directory sizes.

---

# AzuraCast 0.9.7.1 (Oct 12, 2019)

## Bug Fixes

 - This is a minor version release that includes updated Packagist dependencies and resolves issues that prevented Ansible ("Traditional") installations from proceeding. Updating to at least version 0.9.7 is recommended for all users, and 0.9.7.1 is necessary for new users installing via the (mostly unsupported) Ansible installation method.
 
---

# AzuraCast 0.9.7 (Oct 11, 2019)

## New Features

- **New Media Manager**: The Media Manager is one of the core parts of the AzuraCast experience, and we want it to be as smooth and usable as possible, so we rebuilt it from the ground up using the Vue frontend framework. The new media manager features snappier response times, tooltips on buttons, a new "add to playback queue" button, inline renaming of directories, and a brand new inline media editor and album art manager that lets you customize tracks without ever leaving the main media manager page.

- You can now customize even more of the AzuraCast Docker installation parameters by modifying configuration lines in your local [azuracast.env](https://github.com/AzuraCast/AzuraCast/blob/master/azuracast.sample.env#L70-L80) file.

## Bug Fixes

- We have resolved a major issue with one of our third-party libraries that causes "No valid bundles" errors to appear any time AzuraCast attempts to connect to a secure URL. This can happen even on pages that don't seemingly make any external connections (i.e. the media manager), because these pages depend on third-party services (i.e. IP address resolution) to display some part of their data. Updating is strongly recommended to resolve this issue.

- An issue causing updates to fail because of outdated cached Doctrine configuration has been resolved.

- Several parts of the system have been optimized to handle large library sizes without running out of memory. This is an ongoing project that will require more time to complete.

## Technical Notes

- This version includes a significant number of code quality improvements under the hood. You won't notice many of these changes (including optimized Redis session handling, new dependency-injected CLI commands and entity repositories), but they are meant to make AzuraCast's code much more consistent and easier to maintain, which means faster bug fixes and more new features!

---

# AzuraCast 0.9.6.5 (Aug 27, 2019)

## New Features

- This incremental minor version release adds support for AzuraCast's built-in FTP server on Docker installations, which allows you to use your AzuraCast credentials to log in and manage media via a high-performance FTP server.

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

This is a minor release to ensure that all users are on the latest version. It includes a few new features and several bug fixes from version 0.9.6.

## New Features

- **Audit Log**: We now keep track of changes made to important database tables (stations, users, settings, mount points, relays, custom fields, etc.) and log them, along with the specific changes made and the user who made them, in a special reports table. You can view this report via the "Audit Log" link in the global system administration.

- **Per-Mount/Relay Listeners**: We have expanded our listener metrics to show the listener count associated with each individual mount point and remote relay on your station, so you can see which of your mounts/relays are generating the most traffic.

- **Total Listener Hours/Listener CSV Export**: We have added a "Total Listener Hours" calculation to our Listeners report, along with the ability for you to export the report's contents to a CSV file. Many areas use TLH as a measurement of radio royalty payments instead of the Actual Total Performances used by SoundExchange. Check with your local jurisdiction to determine the best way to report listeners for royalty payments.

## Bug Fixes/Other Changes

- By default, the new websocket/eventsource-driven "now playing" API is disabled on new installations, as it has been known to have some issues in certain hosting scenarios. You can always turn it on via the system-wide Settings panel, and if your installation is serving a large number of radio listeners, you are strongly encouraged to do so.

- The public radio player will now show a live DJ's name if one is live.

- Tweaked the AzuraCast AutoDJ's duplicate artist/title prevention algorithm to be more forgiving of playlists that either have no artist or have the same artist on all tracks. 

- Under the hood, we have upgraded our underlying PHP framework (Slim) to version 4, which brings us into much closer compliance with a number of PHP Standards Recommendations, or PSR, specifications.

- The application has been almost completely translated into Russian.

- Updated our PLS/M3U file generation to properly include remote relays and show stream names on clients like VLC.

- Fixed a scenario where hundreds of "zombie processes" were being created by AzuraCast sending notification messages to the nchan websocket now-playing service.

- Fixed a bug where AzuraRelay relays kept being newly recreated every time the relay sent its "Now Playing" update to the parent server.

- Relays themselves (which were previously seen by the system as standard listeners) are now excluded from both listener reports and the total listener count.

- When determining your external-facing IP address (which requires pinging a service that's not located on your installation, one of the few times we do this), we have switched to using our own AzuraCast Central server for this service, so that we can avoid sending your IP to any third-party services. This service simply echoes back your external IP to you via JSON, and the IP isn't used for anything else.

---

# AzuraCast 0.9.6 (Jul 27, 2019)

It's only been just over a month since AzuraCast 0.9.5.1 was released, but we've made some very significant, very important updates to the software in that time, especially in the fields of reliability and performance.

## New Features

- **The AzuraCast AutoDJ is Back**: A few versions ago, we had attempted to switch entirely to using Liquidsoap for our AutoDJ. We've come to realize in the months since then that we actually need our own AutoDJ management component for important reasons, so we brought it back. It's also been improved to include a few new features. Benefits of using the AzuraCast AutoDJ include:
  - Being able to see the next song that will play in the queue,
  - Applying playlist changes without reloading all of AzuraCast, and
  - Avoiding songs playing back-to-back with either the same title _or_ the same artist using our duplicate prevention system.

- **Introducing AzuraRelay**: This version of AzuraCast has built-in support for our new "relay-in-a-box" software [AzuraRelay](https://github.com/AzuraCast/AzuraRelay). Simply drop AzuraRelay onto a server that can run Docker, answer a few quick questions, and it will automatically connect to AzuraCast, detect all stations, and relay them all. It will also list itself as a relay on the "host" AzuraCast station, and report back listener and client details as well.

- **Two New NowPlaying API Sources**: Our most powerful and comprehensive set of data is all compiled into what we call the "Now Playing" API, which is a rich summary of the state of a radio station at the moment. To improve performance of more popular stations using our software, we've introduced two new methods of accessing this data: a static JSON file and a live Websocket/EventSource-driven plugin. You can read more on our new [Now Playing Data APIs Guide](https://www.azuracast.com/developers/nowplaying.html).

## Bug Fixes and Minor Updates

- Icecast has been updated to 2.4.0-kh12.
- By default, the Docker installation has ports open for the first 50 stations, up from the first 10.
- Fixed an issue with automatic port assignment not working in some cases.
- Fonts are now locally hosted along with other static assets, which makes the entire installation self-contained.
- The currently playing song is written atomically to `/var/azuracast/stations/(station_name)/nowplaying.txt`, which is particularly useful for our [radio-video-stream](https://github.com/AzuraCast/radio-video-stream) example project.
- The Docker update script will now check for differences between the latest Docker Compose file and your local copy and only prompt if they're different, and will pull new images before bringing down existing ones, reducing station downtime during updates.
- Updated Ansible installation to ensure it works with Raspberry Pi 3/ARM64 devices.
- If you are leaving a page with a form on it that has unsaved changes, you will be prompted to confirm leaving the page.
- Web servers will now more aggressively cache the hashed static assets used in AzuraCast, improving performance.
- Added a new default album art image.
- Re-added the "clear playlists" button on media management, which does the same thing as clicking "Set Playlists" with songs selected, selecting no playlists, and clicking "Save".

---

# AzuraCast 0.9.5.1 (Jun 11, 2019)

This release is primarily a bug-fix release intended to resolve a number of issues identified in version 0.9.5. There are a few minor but significant new features, and updating is highly recommended for all users.

## New Features

- **Live, Zero-Downtime Backups:** We have switched to a new format for creating backups that allows them to run without causing any outage for your listeners. This new backup format can be run directly from your web browser via the new "Backups" administration page, and you can also configure automated nightly backups. These new backups are portable between both Ansible and Docker installation methods, and can be used to migrate from Ansible to the recommended Docker installation; in fact, we have created a migration script (`docker-migrate.sh`) to do exactly that.

- **Station Clock:** You can now see a live updating clock in the station's time zone underneath the station name on the sidebar menu.

## Technical Notes

- The Highcharts library has been completely replaced across the entire application. This is largely because of Highcharts' license, which allows for free use for non-commercial entities but is not free software. Charts were replaced with the free and open-source `charts.js`, and the listener map was replaced with `leaflet`. This is the last component from early AzuraCast development that required updating to be a fully free and open-source stack.

- Significant portions of the application are now available in both Italian and Czech. A huge thank you as always is due to our translator volunteers for their contributions.

- Some forms across the system would trigger errors that weren't immediately visible (i.e. CSRF validation failures or errors on fields that aren't shown in the current tab); these errors are now displayed in a much more visible format and should be easier to spot.

## Bug Fixes

- Fixed a number of scenarios that caused the "This station is powered by AzuraCast" jingle to play despite a station having an established playlist of music. #1527 #1597

- Fixed error where requested songs would not play during scheduled playlist blocks. #1620

- Fixed an issue where ports weren't unassigned when cloning a new station. #1524

- Fixed various bugs and improve overall performance when processing large collections of new media. #1450

- Fixed browser errors that prevented viewing certain larger log files. #1639

- Fixed the displayed time zones in several station reports to match the station's time zone as set in the database, rather than UTC or local time.

---

# AzuraCast 0.9.5 (May 14, 2019)

Work on AzuraCast never stops, and with the project increasing in popularity, we've been even busier than before. A lot of new improvements have rolled out over the last month, along with a ton of bug fixes. This point release includes:

## New Features

- **Theme Improvements**: We've made some changes to our theme to make it more intuitive, easier to use and more accessible for mobile phone users. Buttons are bigger and clearer, forms are organized in a more concise way, and the station's profile page now gives you direct access to important functions.

- **Schedule Overhaul**: Previously, each user had their own time zone on their profile, and the system had a default time zone setting, but schedules were based on UTC and constantly had to be converted back and forth from the user's local time. This conversion caused a number of problems. To address them, we completely restructured the time zone system. Now, each _station_ has its own time zone, and all scheduled playlists are based on this time zone. Liquidsoap and Icecast/SHOUTcast are also run in this time zone, so schedule times (and logs) will always be consistent.

- **API Parity**: We've done a _lot_ of work to make all of the core functionality of AzuraCast available via our REST API, and we're pleased to announce that as of this release, all major functions are possible entirely via API calls, both for global administration and per-station management functions. As a reminder, you can visit `your-azuracast-url/api` for API documentation specific to your installed version that you can test in-browser against your own installation.

- **A Prettier Public Player**: Our public player has been rewritten as a standalone Vue component (so you can use it for your own custom players, too), and it got a big design update as part of that process. The biggest new feature is the ability to switch between available mount points and remote streams. The player is also now much more resilient to disconnection and will intelligently reconnect after a few seconds.

- Station cloning has been rebuilt from the ground up to fix a number of issues and to improve its performance with large music libraries.

- The "Reorder Playlist" page now includes buttons to manually move playlist items up and down, making the page accessible for those using screen readers.

## Bug Fixes

- Fixed #1382, #1402 and other bugs relating to "don't loop", "play only once" and other special playlist types.

- Per #1400, the Streamers/DJs page will not let you use certain characters in your DJ passwords that are known to cause problems with Liquidsoap authentication.

- Fixed #1405 and other bugs related to the new Liquidsoap AutoDJ process that prevented AzuraCast from getting proper "rich metadata" about the currently playing song (i.e. duration, source playlist, etc).

- Fixed #1499 and #1459, bug reports relating to the default settings used for AAC and OPUS streams in Liquidsoap. We also added the `libsamplerate` library to Liquidsoap, which greatly improves conversion from one sample rate to another (as is often necessary with OPUS streams).

## Other Notes

- As of this release, we are no longer supporting new installations via the Ansible ("Traditional" or "Bare-metal") installation type. These installation types have represented a disproportionate amount of support issues, and have put a very heavy toll on our volunteer support team, so we have removed the Ansible installation instructions from our homepage completely. We will continue to distribute updates to existing users, and we will make our best efforts to continue feature parity between both installation types.

- You can now set playlist schedule times on a per-minute basis via HTML5 `<input type="time">` fields, instead of the 15-minute increment dropdowns.

- The "Once per Day" playlist schedule type has been merged into the main "Scheduled" type. Just set the start and end times to be the same to achieve the same effect. Existing playlists were converted to this format automatically.

- If you have "Normalization and Compression" turned on, this will now use a slightly different function inside Liquidsoap, which produces a cleaner, crisper audio signal.

- If AzuraCast detects that you've modified your installation locally (specifically your Ansible/"Traditional" installation), error reports won't be sent to our Sentry service even if it's turned on. We were getting a huge number of error reports about code we didn't write!

---

# AzuraCast 0.9.4.2 (Mar 31, 2019)

This is another incremental bug-fix release to resolve some outstanding issues with our transition back to using Liquidsoap to drive the majority of the AutoDJ functionality.

## Bug Fixes

- When restarting a station, the AzuraCast error message won't play immediately, but it will play if your station has nothing to play.
- Cue settings provided for files (custom start and end times for the file) are now honored by Liquidsoap again.
- If you make a change to a playlist that should prompt a restart of your station, it will now properly do so.
- The Song Listener Impact report now works properly again.

---

# AzuraCast 0.9.4.1 (Mar 27, 2019)

This release is a very minor release intended to fix a bug identified in 0.9.4 with the initial setup script.

This release also incorporates several playlist changes. As a result of these changes, "Manual AutoDJ" and the regular AzuraCast AutoDJ mode have been merged. Liquidsoap handles the scheduling and playback of all "once every x" and otherwise scheduled playlists, and AzuraCast's AutoDJ handles, by default, the "general rotation" playlists. If an error occurs with AzuraCast's AutoDJ, Liquidsoap will manage general rotation playlists, too.

By having Liquidsoap manage playlists directly, we can now introduce two new settings that weren't possible before:
- You can now have a playlist "interrupt" whatever's playing on the radio; for example, if you have a playlist set to play at exactly 4:00PM, it will stop whatever is playing to play at exactly that time.
- You can set playlists to only loop once, so even if they would play multiple times in their scheduled time block, they'll only play once before Liquidsoap resumes other programming.

We have also made improvements to how Liquidsoap is notified of playlist changes from AzuraCast, so these notifications should be instant as soon as playlists are changed in the web interface.

These features are all rather new and are still in testing, so please report any issues you find with them to us via the GitHub issues page.

---

# AzuraCast 0.9.4 (Mar 25, 2019)

This incremental release features major improvements to our public and embedded players, as well as a number of new features and bug fixes.

## New Features

- **"Jingle Mode" Playlists**: You can now tell AzuraCast to ignore metadata updates for a given playlist, so that when it is played, listeners will hear the audio but won't see the song's title or artist metadata in their player. This is especially useful for playlists that contain jingles or advertisements.

- **User-Selectable Mount Points/Relays**: You can now provide display names for each of your mount points and remote relays, then choose which of them are visible on public pages.

- **Updated Public Player**: We've made significant updates to our public player interface, which now has a live playback progress bar, always-visible player controls, and the ability to select from multiple mount points/relays (see above).

- **Support for Two-Factor Authentication**: Now any user on AzuraCast can significantly improve their security by enabling two-factor authentication when logging in. Our two-factor solution is powered by TOTP one-time passwords and are convenient and secure: just scan the provided QR code with an app on your smartphone (FreeOTP, Authy, or any other TOTP app) and it will generate a unique code every few seconds. You will be asked for this code any time you log in. If you lose access to your authenticator at any time, you can follow the [password reset instructions](https://github.com/AzuraCast/AzuraCast/blob/master/SUPPORT.md#reset-an-account-password) to recover your account.

- **Automatically Send Error Reports**: Thanks to our friends at [Sentry](https://sentry.io/), we've added the ability to automatically send error reports to our team for review. This feature is disabled by default and is opt-in from the System Settings page, and the error reports we receive are anonymized. These error reports can help us diagnose and resolve problems significantly faster.

- The Twitter web hook now has support for rate limiting, so you can tell AzuraCast to only send one Tweet every few seconds, minutes or hours.

- You can now switch your theme from light to dark from anywhere in the application. We've also added a new "Help" page from the dropdown menu that links to our support documentation to help users find common solutions to their problems.

## Updates and Bug Fixes

- The installation method formerly known as "Traditional" has been renamed to "Ubuntu Bare-Metal" installation. The term "traditional" conveyed to many people that this was the "normal" or preferred way of installing AzuraCast, when this is not the case; we heavily recommend the Docker installation method unless you have a specific reason not to use it.

- Liquidsoap on Ubuntu bare-metal installations has been updated to version 1.3.6, offering performance and bug fixes.

- The changes made to playlist weighting in version 0.9.3 have been reverted to the weighting algorithm used in previous versions.

- Further improvements have been made to the process of notifying AzuraCast of song changes, and the station profile will now update much quicker to reflect song changes.

- A number of "yes/no" radio buttons across the application have been replaced with more intuitive single toggle checkboxes.

- Pagination controls on the Media manager have been fixed.

- The playlist form has been divided into tabs for easier navigation, similar to the station form.

---

# AzuraCast 0.9.3 (Feb 26, 2019)

As we start to improve our project stability and more users prefer to use packaged release builds of our software, we are working to produce more frequent incremental releases. This is one such release, primarily consisting of bug fixes and minor under-the-hood improvements over the previous version. All users are encouraged to upgrade.

## Updates and Bug Fixes

- Media uploads have been reworked to process in "batches", improving both performance and reliability of the upload process.

- Several new protections have been introduced to prevent users from deleting themselves, removing critical permissions from their own accounts, or modifying the "super administrator" permission. You can also re-establish any account that previously lost said permissions by running `[your cli command] azuracast:account:set-administrator [your-email-address]`.

- The AutoDJ's weighting tool will factor in the total size of each playlist. If you have one playlist with 5 songs and another with 500, this should greatly prevent the tendency to hear the 5-song playlist far more often than you would otherwise want.

- The AutoDJ will consider the weighting of all types of playlists now, not just general rotation ones, so if you have multiple scheduled playlists on top of one another, it will use weighting to determine which playlist to play from.

- The `station_watcher` secondary process has been removed on Docker installations, replaced by new scripting improvements to our Liquidsoap integration. This allows users of both Docker and Traditional installations to enjoy support for AzuraCast's rich metadata even when using "Manual AutoDJ" mode or "Advanced" playlists.

- Generation of navigation sidebars (both for administration and per-station management) is now managed by the Event Dispatcher, and can be extended by plugins that wish to add new menu items.

- Starting with this release, releases are now tracked on the Composer package manager's [Packagist repository](https://packagist.org/packages/azuracast/azuracast). We will be using Packagist to handle Docker installations and updates, improving maintainability and significantly reducing the overall size of Docker containers further.

---

# AzuraCast 0.9.2 (Feb 7, 2019)

This minor version release includes a number of bug fixes, performance improvements and smaller new features, and is an incremental update on the roadmap to our version 1.0 release.

## Major Updates

- **WebDJ, Stream Live from your Browser**: Thanks to a special feature of our AutoDJ tool Liquidsoap, you can stream directly to your station from your web browser without installing any other software! If your station has both the "Enable DJs/Streamers" and "Enable Public Page" settings turned on, you will see a link for the "Web DJ" on the station sidebar. Note that some browsers only allow this feature to work on HTTPS pages, so make sure you're using a secure connection!

- **New Unified Docker Container**: Optimizations of our Docker infrastructure have allowed us to consolidate the role of 5 of our previous Docker containers into a single unified "Web" container. If you're using Docker, you should update your `docker-compose.yml` file when prompted by the updater script to take advantage of this new infrastructure. After updating, you will need to re-run any LetsEncrypt connection scripts, but once you're set up, IceCast will also be able to take advantage of your LetsEncrypt certificates and certificates will automatically be renewed inside the Docker container.

- **Redesigned Station Profiles and Per-Station File Quotas**: The form you see when editing your station profile has been divided into new tabs for easier use. If you are a global administrator of a multi-tenant AzuraCast instance, you will be able to modify the station's media directory, enabled/disabled status and file upload quota. This quota will be shown to station managers on the "Music Files" page, and is automatically enforced any time a user uploads music from the web interface.

- **Release-Only Updates**: If you prefer to update less frequently, you can now choose to only update to the latest tagged release of AzuraCast instead of using the typical "rolling release" schedule. Both `./update.sh --release` and `./docker.sh update --release` will update their respective installations only if a new tagged version has been released.

## Minor Updates/Technical Notes

- Several new API endpoints have been created, allowing you to use the REST API to manage system settings, branding, users and permissions.

- When viewing your API documentation (`your-azuracast-site/api`), the documentation will allow you to test the endpoints against your own installation instead of the demo instance.

- A new log viewer page has been created for easier diagnosis of issues. Individual station managers can view logs specific to their stations, and global administrators can see all logs across the installation.

- Album art has been moved to the filesystem, resulting in significantly lighter database sizes.

- Under the hood, Flysystem is now being used to access and cache metadata about uploaded station media. This will allow us to expand to support S3 buckets and other remote locations for station media uploads in the future.

- In some previous versions, AzuraCast referred to a third-party CDN (CloudFlare's CDNJS) for many of its static assets for performance reasons. From this version forward, those assets have been moved back to the AzuraCast instance itself. This helps to preserve the self-contained nature of AzuraCast and avoid unnecessary downtime caused by third-party services.

- You can now install another version of SHOUTcast even if a different version is already installed.

## Bug Fixes

- Improvements to log handling and rotation have been made across the system. These changes should significantly reduce the tendency of log files to grow to fill the host filesystem.

- Differences in the handling of remote URLs and sequential playlists between the AutoDJ's normal and "Manual AutoDJ" modes have been reconciled.

- Both frontend and backend dependencies have been updated repeatedly to address security and stability fixes.

- An issue causing a portion of the site to be inaccessible to those using screen readers has been resolved, and other improvements to accessibility have been made across the system.

---

# AzuraCast 0.9.1 (Nov 18, 2018)

This minor version release includes a number of bug fixes, performance improvements and smaller new features, and is an incremental update on the roadmap to our version 1.0 release.

## Major Updates

- **WebDJ, Stream Live from your Browser**: Thanks to a special feature of our AutoDJ tool Liquidsoap, you can stream directly to your station from your web browser without installing any other software! If your station has both the "Enable DJs/Streamers" and "Enable Public Page" settings turned on, you will see a link for the "Web DJ" on the station sidebar. Note that some browsers only allow this feature to work on HTTPS pages, so make sure you're using a secure connection!

- **New Unified Docker Container**: Optimizations of our Docker infrastructure have allowed us to consolidate the role of 5 of our previous Docker containers into a single unified "Web" container. If you're using Docker, you should update your `docker-compose.yml` file when prompted by the updater script to take advantage of this new infrastructure. After updating, you will need to re-run any LetsEncrypt connection scripts, but once you're set up, IceCast will also be able to take advantage of your LetsEncrypt certificates and certificates will automatically be renewed inside the Docker container.

- **Redesigned Station Profiles and Per-Station File Quotas**: The form you see when editing your station profile has been divided into new tabs for easier use. If you are a global administrator of a multi-tenant AzuraCast instance, you will be able to modify the station's media directory, enabled/disabled status and file upload quota. This quota will be shown to station managers on the "Music Files" page, and is automatically enforced any time a user uploads music from the web interface.

- **Release-Only Updates**: If you prefer to update less frequently, you can now choose to only update to the latest tagged release of AzuraCast instead of using the typical "rolling release" schedule. Both `./update.sh --release` and `./docker.sh update --release` will update their respective installations only if a new tagged version has been released.

## Minor Updates/Technical Notes

- Several new API endpoints have been created, allowing you to use the REST API to manage system settings, branding, users and permissions.

- When viewing your API documentation (`your-azuracast-site/api`), the documentation will allow you to test the endpoints against your own installation instead of the demo instance.

- A new log viewer page has been created for easier diagnosis of issues. Individual station managers can view logs specific to their stations, and global administrators can see all logs across the installation.

- Album art has been moved to the filesystem, resulting in significantly lighter database sizes.

- Under the hood, Flysystem is now being used to access and cache metadata about uploaded station media. This will allow us to expand to support S3 buckets and other remote locations for station media uploads in the future.

- In some previous versions, AzuraCast referred to a third-party CDN (CloudFlare's CDNJS) for many of its static assets for performance reasons. From this version forward, those assets have been moved back to the AzuraCast instance itself. This helps to preserve the self-contained nature of AzuraCast and avoid unnecessary downtime caused by third-party services.

- You can now install another version of SHOUTcast even if a different version is already installed.

## Bug Fixes

- Improvements to log handling and rotation have been made across the system. These changes should significantly reduce the tendency of log files to grow to fill the host filesystem.

- Differences in the handling of remote URLs and sequential playlists between the AutoDJ's normal and "Manual AutoDJ" modes have been reconciled.

- Both frontend and backend dependencies have been updated repeatedly to address security and stability fixes.

- An issue causing a portion of the site to be inaccessible to those using screen readers has been resolved, and other improvements to accessibility have been made across the system.

---

# AzuraCast 0.9.0 (Oct 8, 2018)

**Important note:** AzuraCast is a "rolling-release" product that is updated almost daily. If you're running AzuraCast, you're strongly encouraged to update as frequently as possible to get the latest features, bug fixes and security patches. Release numbers are only indicated to chronicle updates over time and give a general idea of an installation's update status.

## Major Updates

- **SHOUTcast 2 DNAS is no longer bundled with AzuraCast.** While SHOUTcast has been a popular offering that was bundled with AzuraCast after significant demand, it has always been non-free, proprietary software, the only component in the AzuraCast stack that is not free and open-source. With the release of SHOUTcast's new ["freemium" pricing structure](https://www.shoutcast.com/Pricing) has also come a new, more aggressive license associated with distribution of the software. As such, AzuraCast can no longer bundle SHOUTcast 2 DNAS with new installations, and we strongly recommend that any stations that can use Icecast do so. We do still support the software, however, and you can manually install it by uploading the `.tar.gz` file provided by SHOUTcast into a new page in the system administration.

- **Our support for Icecast is now even better suited for commercial radio stations.** Along with our withdrawal of out-of-the-box support for SHOUTcast, we've been working hard to make improvements to our Icecast integration so it can better serve commercial radio stations that depend on reliable, accurate reporting. If you're using the latest version of AzuraCast, you will now see much more accurate information on your listeners, especially if you're using the Docker installation or operating behind CloudFlare protection.

- **Sequential Playlists are now supported.** You can now create playlists that must play in a specific order. You can reorder the contents of these playlists at any time from a station's "Playlists" page by clicking the "Reorder" button.

- **Custom metadata can be assigned to any media.** System administrators can define new "custom metadata" fields that will appear when editing any media across the system. These custom fields are selectable when viewing the "Music Files" page and are returned in Now Playing API calls.

- **The station's Profile is now its "home" page.** As we've expanded the Profile page, and as we continue to add new features in the future, this page has quickly become the de-facto main page for overseeing every aspect of a running station. To reflect this, when you click "Manage" next to a station, you'll now immediately be taken there. You can still access the previous home page via "Reports" > "Statistics overview".

- **The core "Now Playing" library is a standalone PHP library now.** We're always looking for new ways to give back to the open-source software community. Now, along with [AzuraForms](https://github.com/AzuraCast/azuraforms), we have spun off our [NowPlaying](https://github.com/AzuraCast/nowplaying) library to be a standalone component you can include in your own PHP code. It's very useful for abstracting out the differences between Icecast, SHOUTcast 1 and SHOUTcast 2 sources into a single return format.

- **AzuraCast now has early support for plugins.** Many of you have wanted to customize the internal workings of AzuraCast without needing to fork the main codebase and maintain your own copy. You can now do this with the help of our new plugin architecture, which lets you hook directly into the most important events that happen "under the hood" in AzuraCast without modifying the core code. Check out our [example plugin](https://github.com/AzuraCast/example-plugin) for a demonstration of what plugins can do.

- **You can now broadcast locally and remotely using the same station.** We have separated out remote relays from the local broadcast, so now instead of having to choose between one or the other, you can both broadcast locally and stream to remote relays using the same station. Any existing remote setup is automatically migrated to this new system when updating AzuraCast.

- **Google APIs are no longer used in the system.** In order to help ensure the privacy of our station operators, you no longer need to use Google Maps or supply a Google API key to take advantage of the live listeners report.

- **AzuraCast has a new homepage!** Visit our simple yet informative new homepage, powered by VuePress, at [azuracast.com](https://www.azuracast.com/).

## Minor Items and Bug Fixes

- Album artwork will now be properly cached by your browser and intermediate services like CloudFlare.

- Responses from the `/api/nowplaying` endpoint will now properly respect the "Force HTTPS" and "Prefer Browser URL" settings, updating URLs to reflect those changes when visited from different hosts or secure pages.

- When editing a station's profile, AzuraCast will automatically check if a port is in use by another station before letting you save changes. This should prevent a very common class of error from ever happening.

- All time zones are selectable, not just one time zone per hour offset, so you can properly select the time zone that reflects your DST settings.

- You can now customize both how long you want to keep song history in the database overall (for reporting) and how many song history items you want to return in the Now Playing API.

- Stations will no longer automatically be assigned to the very popular port 8080, as it's often in use by other processes on the same servers.

- Support for Telegram has been added to web hooks.

---

# AzuraCast 0.8.0 (Dec 15, 2017)

This release includes the cumulative development work done over the fall and winter of 2017 on the AzuraCast project. It includes a number of minor fixes and some small but significant improvements to the system, the user experience, and the security of the application.

## Major Items

- **Album Art support**: Album art is automatically loaded from uploaded music, and can be updated at any time from the media manager. The images are displayed using cache-friendly URLs. All APIs that return "now playing" data now also include album artwork, if available.
- **Broadcast to remote streams:** Running your AutoDJ from AzuraCast but broadcasting to a hosted radio server elsewhere? Now you can directly connect to a remote server for both statistics and broadcasting by visiting the "Mount Points" section of the station manager.

## Minor Enhancements

- **Specify Station "Short URLs"**: You can now customize a station's "stub" URL, as is used in both API calls and the public-facing listener page, independent of the station's name.
- **More Base URL options**: You can choose to prefer to use the current URL the visitor is using in favor of the base URL (i.e. if you have multiple domains that point to one instance, and want the URLs to use whichever one the viewer is looking at). You can also specify if all URLs should force HTTPS.
- **Support for OGG Opus**: You can now add a mount point that specifically broadcasts music using the OGG Opus codec. This is particularly useful if you are building a bot for a voice service like Discord. (Note: due to a known issue, metadata does not transmit properly via Opus, so always make sure to have at least one non-Opus mountpoint running for a station to continue seeing statistics.)
- **Better accessibility**: Thanks to the help of project contributors, the web interface has been optimized for full accessibility when viewed with screen readers or other assistive technology.
- **Hide a station**: You can now hide a station from public view (both from showing a public page and appearing in public-facing APIs) if you intend to use it purely internally or aren't ready to launch it yet.
- **Faster updates to Now Playing data**: Across the system, a new websocket-based update system (powered by nginx and nchan) has been implemented to support live updates as now-playing data changes. Now, song and listener count changes will be reflected immediately, making listener pages far more responsive and accurate.

## Infrastructure

- **Update to PHP 7.2**: Both Docker and Traditional installs have been updated to the newest stable release of PHP, version 7.2.
- **Strengthened Password Security**: The existing bcrypt-based password hashing mechanism is robust and secure, but with the update to PHP 7.2, a newer, even more secure method of storing passwords has been made available via Argon2i. The web app will automatically rehash passwords to the newer, stronger format upon the user's first login.
- **Frontend dependencies relocated to CDNs**: Many of the frontend dependencies used by the web app have been moved and now refer to CDNJS and other CDN sites, so once they've loaded in your system they will perform better. This has also significantly lightened the size of the AzuraCast codebase.
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
