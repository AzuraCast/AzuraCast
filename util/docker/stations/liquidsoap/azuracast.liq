#
# AzuraCast Common Functionality
#

# Default Settings for AzuraCast Instances
init.daemon := false

log.stdout := true
log.file := false

settings.server.socket := true
settings.server.socket.permissions := 0o660

settings.harbor.bind_addrs := ["0.0.0.0"]
settings.encoder.metadata.export := ["artist","title","album","song"]

# Settings
let settings.azuracast = ()

let settings.azuracast.api_url = settings.make(
    description="The API URL used by AzuraCast API calls.",
    ""
)

let settings.azuracast.api_key = settings.make(
    description="An API key authorized to make internal API calls.",
    ""
)

let settings.azuracast.media_path = settings.make(
    description="The base path for local media, or 'api' to make API calls to retrieve media.'",
    "api"
)

# Variables
let azuracast = ()

# True if the first "next song" hasn't been received yet, false if it has.
let azuracast.autodj_is_loading = ref(true)

# Number of attempts that have been made to ping the AutoDJ for the next song.
let azuracast.autodj_ping_attempts = ref(0)

# The username of the last authenticated DJ.
let azuracast.last_authenticated_dj = ref("")

# The username of the current live DJ.
let azuracast.live_dj = ref("")

# Whether Liquidsoap is currently transitioning to a live stream.
let azuracast.to_live = ref(false)

# Whether a DJ is successfully authenticated (and thus live broadcasting is enabled).
let azuracast.live_enabled = ref(false)

# The last non-jingle artist and title metadata.
let azuracast.last_title = ref("")
let azuracast.last_artist = ref("")

# Reimplement LS's now-deprecated drop_metadata function.
def drop_metadata(~id=null(), s)
    let {metadata=_, ...tracks} = source.tracks(s)
    source(id=id, tracks)
end

# Transport for HTTPS outputs.
https_transport = http.transport.ssl()
ignore(https_transport)

# API Calls
def azuracast.api_call(~timeout=10.0, url, payload) =
    full_url = "#{settings.azuracast.api_url()}/#{url}"

    log("API #{url} - Sending POST request to '#{full_url}' with body: #{payload}")
    try
        response = http.post(full_url,
            headers=[
                ("Content-Type", "application/json"),
                ("User-Agent", "Liquidsoap AzuraCast"),
                ("X-Liquidsoap-Api-Key", "#{settings.azuracast.api_key()}")
            ],
            timeout=timeout,
            data=payload
        )

        log("API #{url} - Response (#{response.status_code}): #{response}")
        "#{response}"
    catch err do
        log("API #{url} - Error: #{error.kind(err)} - #{error.message(err)}")
        "false"
    end
end

# AzuraCast protocol implementation.
def azuracast.media_protocol(~rlog=_,~maxtime,arg) =
    let station_media_dir = settings.azuracast.media_path()
    if station_media_dir == "api" then
        timeout = 1000.0 * (maxtime - time())

        j = json()
        j.add("uri", arg)

        [azuracast.api_call(timeout=timeout, "cp", json.stringify(j))]
    else
        ["#{station_media_dir}/#{arg}"]
    end
end

protocol.add(
    "media",
    azuracast.media_protocol,
    doc="Pull files from AzuraCast media directory.",
    syntax="media:uri"
)

# AzuraCast AutoCue Provider
def azuracast.autocue(
    ~request_metadata,
    ~file_metadata=_,
    filename
) =
    # Check if `"azuracast_autocue"` is present and set to `"true"`. If so, use azuracast autocue implementation:
    if list.assoc(default="false", "azuracast_autocue", request_metadata) == "true" then
        def azuracast_meta(label) =
            list.assoc("azuracast_#{label}", request_metadata)
        end

        def azuracast_optional_amplify =
            if list.assoc.mem("azuracast_amplify", request_metadata) then
                { amplify = list.assoc("azuracast_amplify", request_metadata) }
            else
                { }
            end
        end

        {
            cue_in = float_of_string(azuracast_meta("cue_in")),
            cue_out = float_of_string(azuracast_meta("cue_out")),
            fade_in = float_of_string(azuracast_meta("fade_in")),
            fade_out = float_of_string(azuracast_meta("fade_out")),
            start_next = float_of_string(azuracast_meta("start_next")),
            ...azuracast_optional_amplify
        }
    else
        null()
    end
end

# AutoDJ Next Song Script
def azuracast.autodj_next_song() =
    response = azuracast.api_call(
        "nextsong",
        ""
    )
    if (response == "") or (response == "false") then
        null()
    else
        request.create(response)
    end
end

# Delayed ping for AutoDJ Next Song
def azuracast.wait_for_next_song(autodj)
    azuracast.autodj_ping_attempts := azuracast.autodj_ping_attempts() + 1

    if source.is_ready(autodj) then
        log("AutoDJ is ready!")
        azuracast.autodj_is_loading := false
        -1.0
    elsif azuracast.autodj_ping_attempts() > 200 then
        log("AutoDJ could not be initialized within the specified timeout.")
        azuracast.autodj_is_loading := false
        -1.0
    else
        0.5
    end
end

# DJ Authentication
def azuracast.dj_auth(login) =
    auth_info =
        if (login.user == "source" or login.user == "") and (string.match(pattern="(:|,)+", login.password)) then
            auth_string = string.split(separator="(:|,)", login.password)
            {user = list.nth(default="", auth_string, 0),
            password = list.nth(default="", auth_string, 2)}
        else
            {user = login.user, password = login.password}
        end

    response = azuracast.api_call(
        timeout=5.0,
        "auth",
        json.stringify(auth_info)
    )

    if (response == "true") then
        azuracast.last_authenticated_dj := auth_info.user
        true
    else
        false
    end
end

def azuracast.live_connected(header) =
    dj = azuracast.last_authenticated_dj()
    log("DJ Source connected! Last authenticated DJ: #{dj} - #{header}")

    azuracast.live_enabled := true
    azuracast.live_dj := dj

    _ = azuracast.api_call(
        timeout=5.0,
        "djon",
        json.stringify({user = dj})
    )
end

def azuracast.live_disconnected() =
    _ = azuracast.api_call(
        timeout=5.0,
        "djoff",
        json.stringify({user = azuracast.live_dj()})
    )

    azuracast.live_enabled := false
    azuracast.live_dj := ""
end

# Send metadata changes back to AzuraCast
def azuracast.send_feedback(m) =
    def f() =
        if (m["is_error_file"] != "true") then
            if (m["title"] != azuracast.last_title() or m["artist"] != azuracast.last_artist()) then
                azuracast.last_title := m["title"]
                azuracast.last_artist := m["artist"]

                # Only send some metadata to AzuraCast
                def fl(k, _) =
                    tags = ["song_id", "media_id", "playlist_id", "artist", "title"]
                    list.mem(k, tags)
                end

                feedback_meta = list.assoc.filter((fl), metadata.cover.remove(m))

                j = json()
                for item = list.iterator(feedback_meta) do
                    let (tag, value) = item
                    j.add(tag, value)
                end

                _ = azuracast.api_call(
                    "feedback",
                    json.stringify(compact=true, j)
                )
            end
        end
    end

    thread.run(f)
end

# Debugging
def azuracast.log_meta(m)
    label="log_meta"
    l = list.sort.natural(metadata.cover.remove(m))
    list.iter(fun(v) -> log(level=4, label=label, "#{v}"), l)

    nowplaying = ref(m["artist"] ^ " - " ^ m["title"])

    if m["artist"] == "" then
        if string.contains(substring=" - ", m["title"]) then
            let (a, t) = string.split.first(separator=" - ", m["title"])
            nowplaying := a ^ " - " ^ t
        end
    end

    # show `liq_` & other metadata in level 3
    def fl(k, _) =
        tags = ["duration", "media_id", "replaygain_track_gain", "replaygain_reference_loudness"]
        string.contains(prefix="azuracast_", k) or string.contains(prefix="liq_", k) or list.mem(k, tags)
    end

    liq = list.assoc.filter((fl), l)
    list.iter(fun(v) -> log(level=3, label=label, "#{v}"), liq)
    log(level=3, label=label, "Now playing: #{nowplaying()}")
end
