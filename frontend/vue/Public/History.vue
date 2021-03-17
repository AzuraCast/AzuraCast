<template>
    <div id="song_history">
        <now-playing v-bind="$props" @np_updated="setNowPlaying"></now-playing>
        <song-history :history="history"></song-history>
    </div>
</template>

<script>
import SongHistory from './FullPlayer/SongHistory';
import NowPlaying, { nowPlayingProps } from '../Common/NowPlaying';

export default {
    mixins: [nowPlayingProps],
    components: { NowPlaying, SongHistory },
    data () {
        return {
            history: []
        };
    },
    methods: {
        setNowPlaying (np) {
            this.history = np.song_history;
        }
    }
};
</script>
