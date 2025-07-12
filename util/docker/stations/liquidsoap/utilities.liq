let azuracast.utilities = ()

# Reimplement LS's now-deprecated drop_metadata function.
def azuracast.utilities.drop_metadata(~id=null(), s)
    let {metadata=_, ...tracks} = source.tracks(s)
    source(id=id, tracks)
end

# Skip command (used by web UI)
def azuracast.utilities.add_skip_command(s) =
    def skip(_) =
        source.skip(s)
        "Done!"
    end

    server.register(
        namespace="radio", 
        usage="skip", 
        description="Skip the current song.", 
        "skip",
        skip
    )
end

# Register a server/telnet command to update a source's metadata. Returns a new
# source, which will receive the updated metadata. The command has the following
# format: insert key1="val1",key2="val2",...
# @flag extra
# @category Source / Track processing
def azuracast.utilities.add_custom_metadata_command(
    ~id=null(),
    s
) =
    custom_meta_id = string.id.default(default="custom_metadata", id)
    server.insert_metadata(id=custom_meta_id, s)
end
