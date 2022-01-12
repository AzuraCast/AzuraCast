<template>
    <div id="song_history">
        <now-playing v-bind="$props" @np_updated="setNowPlaying"></now-playing>
        <song-history :show-album-art="showAlbumArt" :history="history"></song-history>
    </div>
</template>

<script>
import SongHistory from './FullPlayer/SongHistory';
import NowPlaying, {nowPlayingProps} from '~/components/Common/NowPlaying';

export default {
    mixins: [nowPlayingProps],
    components: {NowPlaying, SongHistory},
    props: {
        showAlbumArt: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            history: []
        };
    },
    methods: {
        setNowPlaying(np) {
            this.history = np.song_history;
        }
    }
};
</script>
