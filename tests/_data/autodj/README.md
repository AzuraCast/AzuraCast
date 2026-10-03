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
  - Defers writes like Doctrine so picks only become visible to the fake queries after a flush
  - Fake queue resets follow repository methods exactly
    - Resync after the bulk write works by refreshing the live entities from the committed rows
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
          ],
          "entries": [
            {
              "playlist_ref": "<playlistRef>",
              "media_ref": "<mediaRef>"
            }
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
    - `entries` (assert all entries of a multi-entry build, e.g. a merged block)
      - a list of per-entry objects supporting `mode`, `playlist_ref`, `media_ref`, `media_any_of`, `from_request` and `playlist_chain_refs`
      - also asserts the build's entry count
      - `distinct: true` changes its meaning on a step with `entries`:
        - it asserts that no media repeats among the entries of this single build
        - the usual cross-step meaning (no repeats within a shuffle cycle) does not apply to such a step
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
- `simulation`
  - Runs consecutive builds over a span of hours and asserts aggregate statistics
    - Run by `QueueBuilderSimulationTest`, in-memory only
  - See [Simulation](#simulation)

A fixture may contain both scheduler and sequence expectations.

Additionally there is the `PlaylistGroupScheduleCoverageTest` which is a small manual test that reuses the in-memory harness to assert group-schedule coverage (the "plays via its group" badge and standalone-play suppression) directly and is not part of the data-driven flow described here but partly relates to it.

## What both harnesses honor

Unless noted, every item below behaves identically in-memory and in integration.

- **Runtime state**
  - all `runtime.*` keys above are applied after import:
    - playlist `played_at` / `queue_reset_at`
    - per-media `is_queued` / `last_played`
    - group-member `is_queued` / `consecutive_plays_count` / `last_played`
    - cued media, queue history & requests
- **Deferred writes**
  - A build's state changes are committed after the build, so queries inside it see the state as of the last commit
  - Commit points: after each build, after each slot of a merged group block, inside every queue reset
  - A queue reset rewrites the committed rows and refreshes the entities from them, dropping any pending change on those rows
  - A column is deferred when the AutoDJ code reads it through SQL (a `WHERE`, an `ORDER BY` or a scalar projection), and stays live when it reads it off a managed entity
  - Limitation: the in-memory flush writes every tracked column, Doctrine only the changed ones
    - The two agree because every bulk write is followed by a resync, in-memory as in the repositories
- **Station-level dump fields**
  - `station.timezone` (schedule windows evaluate in station-local time)
  - `station.requests_only_via_playlists`
  - and the request settings `station.request_delay` / `station.request_threshold`
  - `station.backend_config` (`duplicate_prevention_time_range`, `duplicate_prevention_artist_time_range`, `autodj_queue_length`, `crossfade`), merged into the station defaults
- **Nested groups**
  - Supported to any depth (a group whose member is itself a playlist group)
  - Every group, top-level or nested, honors its own `order` and consecutive-plays rotation.
  - Group members are gated by their own type and schedule:
    - a member is only selectable when the scheduler would play it
    - `Advanced`/`custom` members are never auto-selected
    - windowed types respect their windows
  - ineligible members are skipped so the group advances
  - Members with their own schedule also play standalone via the top-level rotation:
    - only while no ancestor-group chain is scheduled (the group takes precedence)
    - a group without schedule items is as always active
    - members without their own schedule are only reachable through their groups
- **History-based gating**
  - Built rows get their expected play time, visibility and played state the way the AutoDJ `Queue` assigns them:
    - the play time is the build's `now`, following rows of a multi-row build are spaced by track length minus the crossfade overlap
    - jingle rows are hidden
    - rows of an interrupting build are marked played as soon as they are built
  - Seeded `queue_history` entries are played rows at their timestamps
    - Cued media and built rows stay unplayed unless a [simulation](#simulation) marks them played
  - Duplicate prevention reads every unplayed row and played rows only while they are inside the duplicate prevention window
    - Tracks and titles are always checked against the rows inside `duplicate_prevention_time_range`
    - Artists are checked against the rows inside `duplicate_prevention_artist_time_range` when it is set
  - Only unplayed rows count as cued (the loop-once and single-track rules)
  - The OncePerXSongs window reads all rows regardless of played state
  - Cued and freshly built rows rank newer than any seeded history
  - A `queue_history` entry with a `media_ref` gets a faithful `song_id` (needed for
    same-track matching)
    - A raw entry carries its `artist` / `title` for artist/title duplicate
    matching
    - Per-entry `is_visible` and `playlist_ref` drive the OncePerXSongs visibility window
    - Integration also mirrors `queue_history` into `SongHistory`, since the real recently-played check reads that table
  - A playlist group's OncePerXSongs window counts plays of its members at any nesting depth
    - Queue rows record the member and never the group
    - A member that also plays standalone counts against its group's window
- **Requests**
  - Each `runtime.requests` entry becomes a real request that drives the request-playback path:
    - The global request queue and the `Requests`-source playlist
      - A queued request produces a queue entry with its request attached (assert with `from_request: true`)
      - A playlist-served request also has that playlist attached, a global-queue request does not
    - The `station.requests_only_via_playlists` routes between the two:
      - When `true` the global queue is disabled and only a due `Requests`-source playlist serves requests
      - When `false` a due `Requests`-source playlist still preempts the global queue, with the global queue as the fallback outside that playlist's schedule
  - A `Requests`-source member inside a merged group serves multiple requests per block:
    - A `consecutive_plays: N` member serves up to N requests back to back
    - A `play_full_cycle` member drains all currently playable requests before advancing
  - Playlist-served request picks skip any request whose track duplicates the recent history (including the in-progress block's picks); the skipped request stays pending and untouched
- **Requestable rule**
  - A track is only requestable if it sits on at least one enabled playlist with `include_in_requests`
  - The integration importer only generates playlist-referenced media
    - So every `requests` media must live on such a playlist
    - Reuse one where it exists, otherwise add an Advanced (`type: custom`) requestable library playlist
      - Advanced playlists satisfy the requestable check but are never auto-selected
- **Playlist `backend_options`**
  - The `interrupt` restricts a playlist to interrupting builds only (set `interrupting: true` on the step)
  - The `single_track` & schedule `loop_once` drive the scheduled-window special rules
  - The schedule `reset_queue_at_start` resets the playlist's own queue once when its window first becomes active (`queue_reset_at` = window start minus one second)
    - The `reset_queue_recursive` extends that to nested groups and songs members of a playlist group
  - The `merge` on a songs playlist cues the entire playlist in one build
  - The `merge` on a playlist group cues one full rotation pass as a single block per build
    - Sequential/shuffle groups keep picking until the rotation queue empties once (a `consecutive_plays` slot contributes that many tracks, a `play_full_cycle` member its whole remaining cycle; for a `Requests`-source member that cycle is the currently playable request queue)
    - Random groups take one randomized pass with one pick per eligible member (a `play_full_cycle` `Requests`-source member therefore serves only one request per random pass)
    - Assert blocks with the step-level `entries` list
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

## Simulation

A case with a `simulation` block runs the in-memory harness for hours or days in a row instead of asserting single picks. It is meant for long-run behaviour such as fairness and repeat patterns, e.g. a user report of a rotation that repeats every day.

```json
{
  "name": "unique-case-name",
  "now": "2026-09-07T00:00:00+02:00",
  "seed": 12345,
  "simulation": {
    "measured_hours": 96,
    "history_hours": 24,
    "measure_playlists": ["<playlistRef>", "..."],
    "backend_config": {
      "duplicate_prevention_time_range": 120
    },
    "expect": {
      "measured_play_count": 1200,
      "min_repeat_interval_minutes": 120,
      "max_repeat_share_near_window": 0.5,
      "max_repeated_pair_share": 0.02,
      "max_never_played_share": 0.05
    }
  }
}
```

- `now` is the start of the run and `seed` seeds `mt_rand`, which makes the whole run reproducible
- `measured_hours`
  - length of the measured span in hours, counted from the end of `history_hours`
  - the span is split into blocks of the duplicate prevention window, counted from the start of measuring
    - a rotation that loops repeats with the window as its period, so one block covers one loop
    - blocks and `max_repeat_share_near_window` always use `duplicate_prevention_time_range`, also when an artist time range is set
    - the last block is partial when the span is not a multiple of the window
    - `max_repeated_pair_share` needs at least 2 blocks
- `history_hours`
  - hours simulated before measuring starts, to build up queue history until the duplicate prevention window is filled
  - these plays are not measured, but still count as a track's previous play
- `measure_playlists`
  - only plays of these playlists are measured, which keeps jingles and other interruptions out of the statistics
- `backend_config`
  - merged into the station `backend_config` from the dump
  - allows differential cases (e.g. a smaller window or an artist time range) against the same dump
- `expect` needs at least one of:
  - `measured_play_count`: exact number of measured plays (checks clock advance, crossfade and the start of measuring)
  - `min_repeat_interval_minutes`: no measured track may repeat sooner (fails when nothing repeats at all)
  - `max_repeat_share_near_window`: share of repeat intervals within 60 minutes of the duplicate prevention window
  - `max_repeated_pair_share`: share of consecutive measured plays in one block that also followed each other in the previous block
  - `max_never_played_share`: share of the measured playlists' tracks without any measured play

What the run models:

- One build per slot at the slot's start time, the clock then advances by track length minus the crossfade overlap
- Before each build every row that has started playing is marked played
- An empty build advances the clock by one minute; more than 20,000 builds fail the case

Every run writes a report before asserting, to `tests/_output/autodj-simulation/<fixture>-<case>`:

- `.plays.csv` with one row per play of the run
- `.summary.txt` with the settings, each metric next to its expectation, and the statistics behind the metrics

Limitations:

- The `autodj_queue_length` lookahead is not modelled, so no rows are cued ahead of the one being built
- No requests, skips or listener feedback
- The pick kind (strict, least recently played because of a repeated title or artist, unfiltered) is read from the log output of the build
- Runtime grows with the measured hours and the size of the duplicate prevention window, a four-day run over ~500 tracks takes about 10 seconds

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
