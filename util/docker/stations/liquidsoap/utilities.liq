# Reimplement LS's now-deprecated drop_metadata function.
def drop_metadata(~id=null(), s)
    let {metadata=_, ...tracks} = source.tracks(s)
    source(id=id, tracks)
end

# Skip command (used by web UI)
def add_skip_command(s) =
    def skip(_) =
        source.skip(s)
        "Done!"
    end

    server.register(namespace="radio", usage="skip", description="Skip the current song.", "skip",skip)
end
