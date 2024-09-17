#
# AzuraCast Common Functionality
#

# Settings
init.daemon.set(false)

log.stdout.set(true)
log.file.set(false)

settings.server.log.level.set(4)

settings.server.socket.set(true)
settings.server.socket.permissions.set(0o660)

settings.harbor.bind_addrs.set(["0.0.0.0"])
settings.encoder.metadata.export.set(["artist","title","album","song"])

# Variables
azuracast_autodj_is_loading = ref(true)
azuracast_autodj_ping_attempts = ref(0)

azuracast_last_authenticated_dj = ref("")
azuracast_live_dj = ref("")
azuracast_to_live = ref(false)
azuracast_live_enabled = ref(false)

azuracast_last_title = ref("")
azuracast_last_artist = ref("")

# Reimplement LS's now-deprecated drop_metadata function.
def drop_metadata(~id=null(), s)
    let {metadata=_, ...tracks} = source.tracks(s)
    source(id=id, tracks)
end

# Transport for HTTPS outputs.
https_transport = http.transport.ssl()
ignore(https_transport)

# API Calls
def azuracast_api_call(~timeout=10.0, url, payload) =
    full_url = "#{azuracast_api_url}/#{url}"

    log("API #{url} - Sending POST request to '#{full_url}' with body: #{payload}")
    try
        response = http.post(full_url,
            headers=[
                ("Content-Type", "application/json"),
                ("User-Agent", "Liquidsoap AzuraCast"),
                ("X-Liquidsoap-Api-Key", "#{azuracast_api_key}")
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

# AzuraCast AutoCue Provider
def azuracast_autocue(
    ~request_metadata,
    ~file_metadata,
    filename
) =
    # Check if `"azuracast_autocue"` is present and set to `"true"`. If so, use azuracast autocue implementation:
    if list.assoc(default="false", "azuracast_autocue", request_metadata) == "true" then
        def azuracast_meta(label) =
            list.assoc("azuracast_#{label}", request_metadata)
        end

        def azuracast_optional_meta(label) =
            if list.assoc.mem("azuracast_#{label}", request_metadata) then
                [(label, list.assoc("azuracast_#{label}", request_metadata))]
            else
                []
            end
        end

        {
            cue_in = float_of_string(azuracast_meta("cue_in")),
            cue_out = float_of_string(azuracast_meta("cue_out")),
            fade_in = float_of_string(azuracast_meta("fade_in")),
            fade_out = float_of_string(azuracast_meta("fade_out")),
            start_next = float_of_string(azuracast_meta("start_next")),
            ...azuracast_optional_meta("amplify")
        }
    else
        null()
    end
end

# AutoDJ Next Song Script
def azuracast_autodj_next_song() =
    response = azuracast_api_call(
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
def azuracast_wait_for_next_song(autodj)
    azuracast_autodj_ping_attempts.set(azuracast_autodj_ping_attempts() + 1)

    if source.is_ready(autodj) then
        log("AutoDJ is ready!")
        azuracast_autodj_is_loading.set(false)
        -1.0
    elsif azuracast_autodj_ping_attempts() > 200 then
        log("AutoDJ could not be initialized within the specified timeout.")
        azuracast_autodj_is_loading.set(false)
        -1.0
    else
        0.5
    end
end

# DJ Authentication
def azuracast_dj_auth(login) =
    auth_info =
        if (login.user == "source" or login.user == "") and (string.match(pattern="(:|,)+", login.password)) then
            auth_string = string.split(separator="(:|,)", login.password)
            {user = list.nth(default="", auth_string, 0),
            password = list.nth(default="", auth_string, 2)}
        else
            {user = login.user, password = login.password}
        end

    response = azuracast_api_call(
        timeout=5.0,
        "auth",
        json.stringify(auth_info)
    )

    if (response == "true") then
        azuracast_last_authenticated_dj.set(auth_info.user)
        true
    else
        false
    end
end

def azuracast_live_connected(header) =
    dj = azuracast_last_authenticated_dj()
    log("DJ Source connected! Last authenticated DJ: #{dj} - #{header}")

    azuracast_live_enabled.set(true)
    azuracast_live_dj.set(dj)

    _ = azuracast_api_call(
        timeout=5.0,
        "djon",
        json.stringify({user = dj})
    )
end

def azuracast_live_disconnected() =
    _ = azuracast_api_call(
        timeout=5.0,
        "djoff",
        json.stringify({user = azuracast_live_dj()})
    )

    azuracast_live_enabled.set(false)
    azuracast_live_dj.set("")
end

# Send metadata changes back to AzuraCast
def azuracast_send_feedback(m) =
    def f() =
        if (m["is_error_file"] != "true") then
            if (m["title"] != azuracast_last_title() or m["artist"] != azuracast_last_artist()) then
                azuracast_last_title.set(m["title"])
                azuracast_last_artist.set(m["artist"])

                # Only send some metadata to AzuraCast
                def fl(k, _) =
                    tags = ["song_id", "media_id", "playlist_id", "artist", "title"]
                    string.contains(prefix="liq_", k) or string.contains(prefix="replaygain_", k) or list.mem(k, tags)
                end

                feedback_meta = list.assoc.filter((fl), metadata.cover.remove(m))

                j = json()
                for item = list.iterator(feedback_meta) do
                    let (tag, value) = item
                    j.add(tag, value)
                end

                _ = azuracast_api_call(
                    "feedback",
                    json.stringify(compact=true, j)
                )
            end
        end
    end

    thread.run(f)
end

# Debugging
def azuracast_log_meta(m)
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
