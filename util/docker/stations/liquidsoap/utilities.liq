let azuracast.utilities = ()

# Reimplement LS's now-deprecated drop_metadata function.
def azuracast.utilities.drop_metadata(~id:drop_meta_id=null, s)
    let {metadata=_, ...tracks} = source.tracks(s)
    source(id=drop_meta_id, tracks)
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
    ~id:custom_meta_id=null,
    s
) =
    custom_meta_id = string.id.default(default="custom_metadata", custom_meta_id)

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
        namespace=custom_meta_id, 
        usage="insert key1=\"val1\",key2=\"val2\",..", 
        description="Insert a metadata chunk.",
        "insert",
        insert
    )
end
