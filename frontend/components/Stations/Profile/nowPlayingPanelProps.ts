import {nowPlayingProps} from "~/functions/useNowPlaying";

export default {
    ...nowPlayingProps,
    backendType: {
        type: String,
        required: true
    },
    backendSkipSongUri: {
        type: String,
        required: true
    },
    backendDisconnectStreamerUri: {
        type: String,
        required: true
    },
}
