export default {
    numSongs: {
        type: Number,
        required: true
    },
    numPlaylists: {
        type: Number,
        required: true
    },
    backendType: {
        type: String,
        required: true
    },
    hasStarted: {
        type: Boolean,
        required: true
    },
    userCanManageBroadcasting: {
        type: Boolean,
        required: true
    },
    userCanManageMedia: {
        type: Boolean,
        required: true
    },
    manageMediaUri: {
        type: String,
        required: true
    },
    managePlaylistsUri: {
        type: String,
        required: true
    },
    backendRestartUri: {
        type: String,
        required: true
    },
    backendStartUri: {
        type: String,
        required: true
    },
    backendStopUri: {
        type: String,
        required: true
    }
};
