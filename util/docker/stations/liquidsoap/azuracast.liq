#<-----
AzuraCast Common Runtime
----->#

# Default Settings for AzuraCast Instances
init.daemon := false

log.stdout := true
log.file := false

settings.harbor.bind_addrs := ["0.0.0.0"]
settings.encoder.metadata.export := ["artist","title","album","song"]

settings.autocue.amplify_behavior := "keep"

#<-----
Settings
----->#

let settings.azuracast = ()

let settings.azuracast.liquidsoap_api_port = settings.make(
    description="The port number used by Liquidsoap for its HTTP API.",
    8004
)

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

let settings.azuracast.fallback_path = settings.make(
    description="The path for the fallback error message played when no audio is available.",
    "/usr/local/share/icecast/web/error.mp3"
)

let settings.azuracast.temp_path = settings.make(
    description="A temporary path to write ephemeral files (like recordings and HLS segments).",
    "/tmp"
)

let settings.azuracast.compute_autocue = settings.make(
    description="Whether to compute autocue metadata on-the-fly if AzuraCast does not have values specified.",
    false
)

let settings.azuracast.default_fade = settings.make(
    description="The default fade-in/fade-out value (in seconds).",
    0.0
)

let settings.azuracast.default_cross = settings.make(
    description="The default cross duration (in seconds).",
    0.0
)

let settings.azuracast.enable_crossfade = settings.make(
    description="Whether to enable crossfading by default.",
    false
)

let settings.azuracast.crossfade_type = settings.make(
    description="The type of crossfade (if enabled), between 'normal' and 'smart'.",
    "normal"
)

let settings.azuracast.request_timeout = settings.make(
    description="The timeout for requests (i.e. song requests and 'Next Song' resolution), in seconds.",
    20.0
)

let settings.azuracast.http_timeout = settings.make(
    description="The timeout for HTTP API requests, in seconds.",
    10.0
)

let settings.azuracast.live_broadcast_text = settings.make(
    description="The placeholder metadata when live DJs connect with no initial metadata.",
    "Live Broadcast"
)

let settings.azuracast.apply_amplify = settings.make(
    description="Apply amplification metadata to the stream signal.",
    true
)

#<-----
Variables
----->#

let azuracast = ()

# True if the first "next song" hasn't been received yet, false if it has.
let azuracast.autodj_is_loading = ref(true)

# Number of attempts that have been made to ping the AutoDJ for the next song.
let azuracast.autodj_ping_attempts = ref(0)

# The username/display name of the last authenticated DJ.
let azuracast.last_authenticated_dj = ref("")
let azuracast.last_authenticated_dj_name = ref("")

# The username/display name of the current live DJ.
let azuracast.live_dj = ref("")
let azuracast.live_dj_name = ref("")

# Whether Liquidsoap is currently transitioning to a live stream.
let azuracast.to_live = ref(false)

# Whether a DJ is successfully authenticated (and thus live broadcasting is enabled).
let azuracast.live_enabled = ref(false)

# The last non-jingle artist and title metadata.
let azuracast.last_title = ref("")
let azuracast.last_artist = ref("")

#<-----
Utility Functions
----->#

%include "./crossfade.liq"
%include "./utilities.liq"

# Transport for HTTPS outputs.
https_transport = http.transport.ssl()
ignore(https_transport)

#<-----
Liquidsoap HTTP API
----->#

def azuracast.http_api_log_requests(req, res, next)
    started_at = time()
    next(req, res)
    req_duration = time() - started_at

    log(label="http", "#{req.method} #{req.path} #{res.status_code.current()} #{req_duration}")
end

harbor.http.middleware.register(azuracast.http_api_log_requests)
  
def azuracast.http_api_check_token(req, res, next)
    if req.headers["x-liquidsoap-api-key"] != settings.azuracast.api_key() then
        res.status_code(401)
    else
        next(req, res)
    end
end

harbor.http.middleware.register(azuracast.http_api_check_token)

def azuracast.start_http_api(~port=null) = 
    port = port ?? settings.azuracast.liquidsoap_api_port()
    server.harbor(port=port)
end

#<-----
API Utilities
----->#

def azuracast.api_call(~timeout=null, endpoint_url, payload) =
    timeout = timeout ?? settings.azuracast.http_timeout()
    full_url = "#{settings.azuracast.api_url()}/#{endpoint_url}"

    log(
        level=3,
        label="azuracast.api",
        "API #{endpoint_url} - Sending POST request to '#{full_url}' with body: #{payload}"
    )

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

        log(
            level=3,
            label="azuracast.api",
            "API #{endpoint_url} - Response (#{response.status_code}): #{response}"
        )

        if response.status_code != 200 then
            null
        else
            "#{response}"
        end
    catch err do
        log(
            level=2,
            label="azuracast.api",
            "API #{endpoint_url} - Error: #{error.kind(err)} - #{error.message(err)}"
        )
        
        null
    end
end

# AzuraCast protocol implementation.
def azuracast.media_protocol(~rlog:_,~maxtime,arg) =
    station_media_dir = settings.azuracast.media_path()
    if station_media_dir == "api" then
        timeout = 1000.0 * (maxtime - time())

        j = json()
        j.add("uri", arg)

        try
            api_response = azuracast.api_call(timeout=timeout, "cp", json.stringify(j))

            if null.defined(api_response) then
                let json.parse (
                    {
                        uri,
                        isTemp
                    } : {
                        uri: string,
                        isTemp: bool
                    }
                ) = null.get(api_response)

                if isTemp then
                    "tmp:#{uri}"
                else
                    "#{uri}"
                end
            else 
                null
            end
        catch err do
            log(
                level=1,
                label="azuracast.media_protocol",
                "ERROR parsing JSON: #{err}"
            )

            null
        end
    else
        "#{station_media_dir}/#{arg}"
    end
end

protocol.add(
    "media",
    azuracast.media_protocol,
    doc="Pull files from AzuraCast media directory.",
    syntax="media:uri"
)

#<-----
AutoCue
----->#

def azuracast.autocue(
    ~request_metadata,
    ~file_metadata,
    filename
) =
    label="azuracast.autocue"

    # Check if `"azuracast_autocue"` is present and set to `"true"`. If so, use azuracast autocue implementation:
    if list.assoc(default="false", "azuracast_autocue", request_metadata) == "true" then
        def azuracast_optional_start_next = 
            if list.assoc.mem("autocue_start_next", request_metadata) then
                { start_next = float_of_string(list.assoc("autocue_start_next", request_metadata)) }
            else
                { }
            end
        end

        def azuracast_optional_amplify =
            if list.assoc.mem("liq_amplify", request_metadata) then
                { amplify = list.assoc("liq_amplify", request_metadata) }
            else
                { }
            end
        end

        autocue_result = {
            cue_in = float_of_string(list.assoc("autocue_cue_in", request_metadata)),
            cue_out = float_of_string(list.assoc("autocue_cue_out", request_metadata)),
            fade_in = float_of_string(list.assoc("autocue_fade_in", request_metadata)),
            fade_out = float_of_string(list.assoc("autocue_fade_out", request_metadata)),
            ...azuracast_optional_start_next,
            ...azuracast_optional_amplify
        }

        log(
            level=3,
            label=label,
            "AzuraCast AutoCue data: #{autocue_result}."
        )

        autocue_result
    elsif settings.azuracast.compute_autocue() then
        log(
            level=2,
            label=label,
            "Cache missed; computing AutoCue values."
        )

        let autocue_result = autocue.internal.implementation(
            request_metadata=request_metadata, 
            file_metadata=file_metadata, 
            filename
        )

        # Save cache back to AzuraCast.
        if list.assoc(default="", "azuracast_cache_key", request_metadata) != "" then
            cache_key = list.assoc("azuracast_cache_key", request_metadata);
            log(
                level=2,
                label=label,
                "Saving cache to AzuraCast (key #{cache_key})"
            )
            
            def f() =
                _ = azuracast.api_call(
                    timeout=5.0,
                    "savecache",
                    json.stringify({
                        cache_key = cache_key,
                        data = autocue_result
                    })
                )
            end
        
            thread.run(fast=false, f)
        end

        autocue_result
    else
        null
    end
end

enable_autocue_metadata()
autocue.register(name="azuracast", azuracast.autocue)
settings.autocue.preferred := "azuracast"

#<-----
AutoDJ
----->#

# AutoDJ Next Song Script
def azuracast.autodj_next_song() =
    try
        api_response = azuracast.api_call(
            "nextsong",
            ""
        )

        if null.defined(api_response) then
            let json.parse (
                {
                    uri,
                } : {
                    uri: string,
                }
            ) = null.get(api_response)

            request.create(uri)
        else 
            null
        end
    catch err do
        log(
            level=1,
            label="azuracast.autodj",
            "ERROR parsing JSON: #{err}"
        )

        null
    end
end

# Delayed ping for AutoDJ Next Song
def azuracast.wait_for_next_song(autodj) = 
    azuracast.autodj_ping_attempts := azuracast.autodj_ping_attempts() + 1

    if source.is_ready(autodj) then
        log(
            level=2,
            label="azuracast.autodj",
            "AutoDJ is ready!"
        )

        azuracast.autodj_is_loading := false
        -1.0
    elsif azuracast.autodj_ping_attempts() > 200 then
        log(
            level=1,
            label="azuracast.autodj",
            "ERROR: AutoDJ could not be initialized within the specified timeout."
        )

        azuracast.autodj_is_loading := false
        -1.0
    else
        0.5
    end
end

def azuracast.enable_autodj(s) =
    dynamic = request.dynamic(
        id="next_song", 
        timeout=settings.azuracast.request_timeout(), 
        retry_delay=10., 
        azuracast.autodj_next_song
    )

    dynamic_startup = fallback(
        id = "dynamic_startup",
        track_sensitive = false,
        [
            dynamic,
            source.available(
                blank(id = "autodj_startup_blank", duration = 120.),
                predicate.activates({azuracast.autodj_is_loading()})
            )
        ]
    )
    
    s = fallback(id="autodj_fallback", track_sensitive = true, [dynamic_startup, s])

    ref_dynamic = ref(dynamic);
    thread.run.recurrent(delay=0.25, { azuracast.wait_for_next_song(ref_dynamic()) })

    s
end

#<-----
Live Streamers/DJs
----->#

# DJ Authentication
def azuracast.dj_auth(auth_info) =
    try
        api_response = azuracast.api_call(
            timeout=5.0,
            "auth",
            json.stringify(auth_info)
        )

        if null.defined(api_response) then
            let json.parse (
                {
                    allow,
                    username,
                    display_name,
                } : {
                    allow: bool,
                    username: string?,
                    display_name: string?,
                }
            ) = null.get(api_response)

            if allow then
                azuracast.last_authenticated_dj := null.get(default='', username)
                azuracast.last_authenticated_dj_name := null.get(
                    default=settings.azuracast.live_broadcast_text(),
                    display_name
                )

                true
            else
                false
            end
        else 
            false
        end
    catch err do
        log(
            level=1,
            label="azuracast.streamer",
            "ERROR parsing JSON: #{err}"
        )

        false
    end
end

def azuracast.live_connected(header) =
    dj = azuracast.last_authenticated_dj()
    dj_name = azuracast.last_authenticated_dj_name()

    log(
        level=2,
        label="azuracast.dj",
        "DJ Source connected! Last authenticated DJ: #{dj} (#{dj_name}) - #{header}"
    )

    azuracast.live_enabled := true
    azuracast.live_dj := dj
    azuracast.live_dj_name := dj_name

    def f() =
        _ = azuracast.api_call(
            timeout=5.0,
            "djon",
            json.stringify({
                user = azuracast.live_dj()
            })
        )
    end

    thread.run(fast=false, f)
end

def azuracast.live_disconnected() =
    azuracast.live_enabled := false

    def f() =
        _ = azuracast.api_call(
            timeout=5.0,
            "djoff",
            json.stringify({
                user = azuracast.live_dj()
            })
        )

        azuracast.live_dj := ""
        azuracast.live_dj_name := ""
    end

    thread.run(fast=false, f)
end

#<-----
Live-aware Crossfade
----->#

def azuracast.live_aware_crossfade_impl(old, new) =
    log.info(label="azuracast.crossfade", "Crossfading")
    list.iter(
        fun (m) -> log.info(label="azuracast.crossfade", "Old metadata: #{fst(m)} -> #{snd(m)}"),
        metadata.cover.remove(old.metadata)
    )
    list.iter(
        fun (m) -> log.info(label="azuracast.crossfade", "New metadata: #{fst(m)} -> #{snd(m)}"),
        metadata.cover.remove(new.metadata)
    )
    if azuracast.to_live() then
        # If going to the live show, play a simple sequence
        # fade out AutoDJ, do (almost) not fade in streamer
        log.info(label="azuracast.crossfade", "Fading to live...")
        sequence([
            fade.out(duration=settings.azuracast.default_fade(), old.source),
            fade.in(duration=settings.azuracast.default_fade(), new.source)
        ])
    elsif settings.azuracast.enable_crossfade() then
        if settings.azuracast.crossfade_type() == "smart" then
            log.info(label="azuracast.crossfade", "Smart crossfade...")
            cross.smart(
                old,
                new,
                margin=8.,
                fade_in=settings.azuracast.default_fade(),
                fade_out=settings.azuracast.default_fade()
            )
        else
            log.info(label="azuracast.crossfade", "Simple crossfade...")
            cross.simple(
                old.source,
                new.source,
                fade_in=settings.azuracast.default_fade(),
                fade_out=settings.azuracast.default_fade()
            )
        end
    else
        # Otherwise, use a beautiful add
        log.info(label="azuracast.crossfade", "Add crossfade...")
        add(normalize=false, [
            fade.in(
                initial_metadata=new.metadata,
                duration=settings.azuracast.default_fade(),
                new.source
            ),
            fade.out(
                initial_metadata=old.metadata,
                duration=settings.azuracast.default_fade(),
                old.source
            )
        ])
    end
end

def azuracast.apply_crossfade(s) =
    cross(
        duration=settings.azuracast.default_cross(),
        azuracast.live_aware_crossfade_impl,
        s
    )
end

#<-----
Feedback API
----->#

# Send metadata changes back to AzuraCast
def azuracast.send_feedback(m) =
    if (m["is_error_file"] != "true") then
        if (m["title"] != azuracast.last_title() or m["artist"] != azuracast.last_artist()) then
            azuracast.last_title := m["title"]
            azuracast.last_artist := m["artist"]

            # Only send some metadata to AzuraCast
            def fl(k, _) =
                tags = ["song_id", "media_id", "playlist_id", "sq_id", "artist", "title"]
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

#<-----
Fallback
----->#

def azuracast.add_fallback(s) =
    error_file = single(id="error_jingle", "annotate:liq_disable_autocue=\"true\":#{settings.azuracast.fallback_path()}")

    def tag_error_file(_) =
        [("is_error_file", "true")]
    end
    error_file = metadata.map(tag_error_file, error_file)

    s = fallback(id="safe_fallback", track_sensitive = false, [s, error_file])
    s
end

def azuracast.handle_jingle_mode(s) =
    last_metadata = ref([])

    def handle_jingle_mode(m) =
        if (m["jingle_mode"] == "true") then
            last_metadata()
        else
            last_metadata.set(m)
            m
        end
    end

    s = metadata.map(update=false, strip=true, handle_jingle_mode, s)
    s
end

def azuracast.apply_amplify(s) = 
    if (settings.azuracast.apply_amplify()) then
        (amplify(1., s) : source)
    else
        s
    end
end

#<-----
Debugging
----->#

def azuracast.log_meta(m) = 
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
