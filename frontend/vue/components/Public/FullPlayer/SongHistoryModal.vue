<template>
    <b-modal
        id="song_history_modal"
        ref="modal"
        size="md"
        :title="$gettext('Song History')"
        centered
        hide-footer
    >
        <song-history
            :show-album-art="showAlbumArt"
            :history="history"
        />
    </b-modal>
</template>

<script setup>
import SongHistory from './SongHistory';

const props = defineProps({
    history: {
        type: Array,
        default: () => {
            return [];
        }
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
});
</script>
