# Reimplement LS's now-deprecated drop_metadata function.
def drop_metadata(~id=null, s)
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

# Register a server/telnet command to update a source's metadata. Returns a new
# source, which will receive the updated metadata. The command has the following
# format: insert key1="val1",key2="val2",...
# @flag extra
# @category Source / Track processing
def server.insert_metadata(
    ~id=null,
    s
) =
    id = string.id.default(default="custom_metadata", id)

    def insert(meta_str) =
        let (meta, _) = string.annotate.parse("#{meta_str}:")
        if
            meta != []
        then
            source.methods(s).insert_metadata(meta)
            "Done"
        else
            "Syntax error or no metadata given. Use key1=\"val1\",key2=\"val2\",.."
        end
    end

    server.register(
        namespace=id, 
        usage="insert key1=\"val1\",key2=\"val2\",..", 
        description="Insert a metadata chunk.",
        "insert",
        insert
    )
end
