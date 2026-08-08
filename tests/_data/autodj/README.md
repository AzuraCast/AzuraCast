# AutoDJ Test Fixtures

This directory contains Data-driven fixtures for the AutoDJ **scheduling** and **queuing** test suites.

A fixture is a pair of files sharing a base name:

- `<name>.dump.json`
  - A playlist configuration snapshot (the format produced by `azuracast:playlist:export` / the "Export Config (JSON)" UI action)
- `<name>.scenario.json`
  - Contains the frozen "now", any runtime state and the expected outcome for one of multiple cases

## Overview

The **same** fixtures are executed by two harnesses, both using the real (not mocked)
[Scheduler](../../../backend/src/Radio/AutoDJ/Scheduler.php) and
[QueueBuilder](../../../backend/src/Radio/AutoDJ/QueueBuilder.php):

- **In-memory** (`tests/Unit/AutoDJ/`)
  - Fast, no database involved
  - Hydrates an in-memory entity store from the dumped data
  - Runs against fake repositories that emulate the repositories behaviour
- **Integration** (`tests/Functional/AutoDjIntegrationCest.php`)
  - Authoritative tests against the database
  - Imports the dump into a test station
    - Re-linking existing media, or generating silent placeholders via ffmpeg

If the two harnesses disagree on a case, that is a fake-vs-reality drift bug to fix and the integration suites result is most likely correct.

## Directory layout

The `scheduling/`, `queuing/`, and `requests/` sub directories are organisational only, both harnesses run every fixture.

Fixtures must be placed exactly one directory deep under `tests/_data/autodj/`.

## Scenario format

For a quick reference example of the scenario format see the following .json structure. This is parsed into the [ScenarioFile](../../../backend/src/Tests/AutoDJ/Scenario/ScenarioFile.php) DTO, refer to that for more detailed information about the exact data types.

```json
{
  "description": "Scenario format example",
  "modes": [
    "in_memory",
    "integration"
  ],
  "cases": [
    {
      "name": "unique-case-name",
      "now": "2018-01-15T22:30:00+00:00",
      "seed": 12345,
      "runtime": {
        "playlists": {
          "<playlistRef>": {
            "played_at": "...",
            "queue_reset_at": null
          }
        },
        "playlist_media": {
          "<playlistRef>:<mediaRef>": {
            "is_queued": false,
            "last_played": 1515000000
          }
        },
        "group_members": {
          "<containerRef>:<memberRef>": {
            "is_queued": false,
            "consecutive_plays_count": 1,
            "last_played": 1515000000
          }
        },
        "cued_media": [
          {
            "playlist_ref": "<playlistRef>",
            "media_ref": "<mediaRef>"
          }
        ],
        "queue_history": [
          {
            "media_ref": "<mediaRef>",
            "playlist_ref": "<playlistRef>",
            "timestamp_played": 1515000000,
            "is_visible": true
          }
        ],
        "requests": [
          {
            "media_ref": "<mediaRef>",
            "skip_delay": true
          }
        ]
      },
      "expect_should_play": {
        "<playlistRef>": true
      },
      "expect_schedule_play": {
        "<playlistRef>#0": true
      },
      "expect_sequence": [
        {
          "now": "2018-01-15T12:30:00+00:00",
          "mode": "exact",
          "interrupting": false,
          "playlist_ref": "<playlistRef>",
          "media_ref": "<mediaRef>",
          "media_any_of": [
            "<mediaRef>",
            "..."
          ],
          "distinct": true,
          "from_request": true,
          "playlist_chain_refs": [
            "<rootRef>",
            "<directRef>"
          ]
        }
      ]
    }
  ]
}
```

### Field notes:

- `modes`
  - which harnesses run this fixture
  - defaults to both when omitted
- `now`
  - the point in time the case is evaluated at, as an absolute ISO 8601 timestamp
  - frozen via `CarbonImmutable::setTestNow` in the tests
- `seed`
  - seeds `mt_rand` for weighted-shuffle determinism
  - optional
- `runtime`
  - applied after import to have a known state to check against
- `expect_should_play`
  - keyed by playlist ref
- `expect_schedule_play`
  - keyed by `<playlistRef>#<scheduleIndex>`
- `expect_sequence`
  - one step per successive build
  - per step:
    - `now` (optional, advances the frozen clock)
    - `mode` (`exact` | `membership` | `none`, default `exact`)
    - `interrupting` (run as an interrupting build)
    -  `playlist_ref` / `media_ref` (exact match)
    - `media_any_of` (membership match)
    - `distinct` (media must not repeat within a shuffle cycle)
    - `from_request` (assert the pick did / did not come from a request)
    - `playlist_chain_refs` (assert the entry's playlist chain, root to direct)
- `queue_history`
  - entries take `media_ref` (or a raw `song_id` / `artist` / `title` for a track not in
  the dump)
  - a `timestamp_played`
  - an `playlist_ref`
  - and an `is_visible` (default `true`)
- `requests`
  - entries take `media_ref` (required)
  - `skip_delay` (default `true`)
  - an optional `timestamp` (ISO string or epoch seconds)
  - and `played` (default `false`; `true` seeds an already-consumed request)

### Expectation types

Each case carries one or more expectation blocks, run by different test classes:

- `expect_should_play` / `expect_schedule_play`
  - Assert scheduler eligibility (`shouldPlaylistPlayNow` and `shouldSchedulePlayNow`),
    - Run by `SchedulerCasesTest`
- `expect_sequence`
  - Assert the tracks `QueueBuilder` selects to play
    - Run by `QueueBuilderCasesTest`
  - Each step is one successive build
    - State (played_at, queue position, consecutive plays, history) advances between steps, so a sequence verifies a real run of picks
    - A single expected track is just a one-step sequence

A fixture may contain both scheduler and sequence expectations.

Additionally there is the `PlaylistGroupScheduleConflictTest` which is a small manual test that reuses the in-memory harness to assert group-schedule blocking directly and is not part of the data-driven flow described here but partly relates to it.

## What both harnesses honor

Unless noted, every item below behaves identically in-memory and in integration.

- **Runtime state**
  - all `runtime.*` keys above are applied after import:
    - playlist `played_at` / `queue_reset_at`
    - per-media `is_queued` / `last_played`
    - group-member `is_queued` / `consecutive_plays_count` / `last_played`
    - cued media, queue history & requests
- **Station-level dump fields**
  - `station.timezone` (schedule windows evaluate in station-local time)
  - `station.requests_only_via_playlists`
  - and the request settings `station.request_delay` / `station.request_threshold`
- **Nested groups**
  - Supported to any depth (a group whose member is itself a playlist group)
  - Every group, top-level or nested, honors its own `order` and consecutive-plays rotation.
  - Group members are gated by their own type and schedule:
    - a member is only selectable when the scheduler would play it
    - `Advanced`/`custom` members are never auto-selected
    - windowed types respect their windows
  - ineligible members are skipped so the group advances
- **History-based gating**
  - Built entries, cued media, and seeded `queue_history` all become unplayed queue rows that feed the OncePerXSongs window and duplicate prevention
  - Cued and freshly built rows rank newer than any seeded history
  - A `queue_history` entry with a `media_ref` gets a faithful `song_id` (needed for
    same-track matching)
    - A raw entry carries its `artist` / `title` for artist/title duplicate
    matching
    - Per-entry `is_visible` and `playlist_ref` drive the OncePerXSongs visibility window
    - Integration also mirrors `queue_history` into `SongHistory`, since the real recently-played check reads that table
- **Requests**
  - Each `runtime.requests` entry becomes a real request that drives the request-playback path:
    - The global request queue and the `Requests`-source playlist
      - A queued request produces a queue entry with its request attached (assert with `from_request: true`)
      - A playlist-served request also has that playlist attached, a global-queue request does not
    - The `station.requests_only_via_playlists` routes between the two:
      - When `true` the global queue is disabled and only a due `Requests`-source playlist serves requests
      - When `false` a due `Requests`-source playlist still preempts the global queue, with the global queue as the fallback outside that playlist's schedule
- **Requestable rule**
  - A track is only requestable if it sits on at least one enabled playlist with `include_in_requests`
  - The integration importer only generates playlist-referenced media
    - So every `requests` media must live on such a playlist
    - Reuse one where it exists, otherwise add an Advanced (`type: custom`) requestable library playlist
      - Advanced playlists satisfy the requestable check but are never auto-selected
- **Playlist `backend_options`**
  - The `interrupt` restricts a playlist to interrupting builds only (set `interrupting: true` on the step)
  - The `single_track` & schedule `loop_once` drive the scheduled-window special rules
- **Remote stream playlists are excluded from queueing**
  - A playlist may set `config.source: "remote_url"` with `config.remote_type`
  - A `stream` (or `other`) remote is filtered out before it reaches the scheduler
  - Assert it with `expect_sequence` (a real build) rather than `expect_should_play`
    - The scheduler does not filter on source and would report it as schedulable
    - A `remote_type: "playlist"` source is played via an HTTP fetch and is out of scope for these fixtures

## Determinism rules

- **Exact picks**
  - Make exactly one playlist eligible at the winning priority bucket
    - Priority order is `OncePerHour -> OncePerXSongs -> OncePerXMinutes -> Standard`, scheduled before unscheduled
  - Use `order: sequential` with explicit `weight`s to pin the media pick
- **Random & shuffle order**
  - `order: random` (and shuffle-after-reset) is non-deterministic
    - In-memory it is shuffled in PHP, which `mt_srand` controls
      - Set `seed` to make the shuffle reproducible
      - To assert an exact order, also restrict the fixture to `in_memory` mode (the seed only pins the in-memory run)
    - In the database it uses `ORDER BY RAND()`, which `mt_srand` does not control
      - Stays non-deterministic even with a seed
      - Assert these with `mode: membership`
- **Requests**
  - Carry a small random delay unless `skip_delay` is set
    - Keep requests `skip_delay: true` (always playable)
    - Or set `station.request_delay: 0` and pin `timestamp` to exercise the "delay not yet satisfied" branch
  - The recently-played threshold is deterministic given seeded `queue_history`

## Adding a new case

1. Capture the setup
   - Use the CLI command `azuracast:playlist:export <station> -o tests/_data/autodj/<area>/<name>.dump.json`
   - Or export it via the `Export` / `Export JSON` buttons in the UI
   - Or hand-write a minimal dump
2. Create `<name>.scenario.json`
   - Set the `now`, any `runtime` overrides, and the expected outcome
3. Run it
   - `codecept run Unit AutoDJ` (fast, in-memory)
   - `codecept run Functional AutoDjIntegrationCest` (DB, integration)

Console and `codecept` commands should be run inside the container, e.g.
`docker exec azuracast codecept run Unit AutoDJ` in order to have the correct environment available.
