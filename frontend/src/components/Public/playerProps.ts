import {nowPlayingProps} from "~/functions/useNowPlaying";

export default {
    ...nowPlayingProps,
    showHls: {
        type: Boolean,
        default: true
    },
    hlsIsDefault: {
        type: Boolean,
        default: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    autoplay: {
        type: Boolean,
        default: false
    },
}
