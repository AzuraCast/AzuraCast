<template>
    <b-modal size="md" id="song_history_modal" ref="modal" :title="$gettext('Song History')" centered hide-footer>
        <song-history :show-album-art="showAlbumArt" :history="history"></song-history>
    </b-modal>
</template>

<script setup>
import SongHistory from './SongHistory';

const props = defineProps({
    history: Array,
    showAlbumArt: {
        type: Boolean,
        default: true
    },
});
</script>
