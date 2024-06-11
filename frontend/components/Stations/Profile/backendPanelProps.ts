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
