<template>
    <b-modal size="md" id="song_history_modal" ref="modal" :title="langTitle" centered hide-footer>
        <song-history :show-album-art="showAlbumArt" :history="history"></song-history>
    </b-modal>
</template>

<script>
import SongHistory from './SongHistory';

export default {
    components: {SongHistory},
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
    computed: {
        langTitle() {
            return this.$gettext('Song History');
        }
    },
    methods: {
        updateHistory (np) {
            this.history = np.song_history;
        }
    }
};
</script>
