import NowPlaying from '~/components/Entity/NowPlaying';
import {onMounted, shallowRef, watch} from "vue";
import {useEventSource} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

export const nowPlayingProps = {
    nowPlayingUri: {
        type: String,
        required: true
    },
    useSse: {
        type: Boolean,
        required: false,
        default: false
    },
    sseUri: {
        type: String,
        required: false,
        default: null
    },
    initialNowPlaying: {
        type: Object,
        default() {
            return NowPlaying;
        }
    }
};

export default function useNowPlaying(props) {
    const np = shallowRef(props.initialNowPlaying);

    const setNowPlaying = (np_new) => {
        np.value = np_new;

        // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: np_new.now_playing.song.title,
                artist: np_new.now_playing.song.artist,
                artwork: [
                    {src: np_new.now_playing.song.art}
                ]
            });
        }

        document.dispatchEvent(new CustomEvent("now-playing", {
            detail: np_new
        }));
    }

    if (props.useSse) {
        const {data} = useEventSource(props.sseUri);
        watch(data, (sse_data_raw) => {
            const sse_data = JSON.parse(sse_data_raw);
            const sse_np = sse_data?.pub?.data?.np || null;

            if (sse_np) {
                setTimeout(() => {
                    setNowPlaying(sse_np);
                }, 3000);
            }
        });
    } else {
        const {axios} = useAxios();
        const checkNowPlaying = () => {
            axios.get(props.nowPlayingUri, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                }
            }).then((response) => {
                setNowPlaying(response.data);

                setTimeout(checkNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch(() => {
                setTimeout(checkNowPlaying, (!document.hidden) ? 30000 : 120000);
            });
        }

        onMounted(() => {
            setTimeout(checkNowPlaying, 5000);
        });
    }

    return np;
}
