<template>
    <modal
        id="song_history_modal"
        ref="$modal"
        size="md"
        :title="$gettext('Song History')"
        centered
    >
        <song-history
            :show-album-art="showAlbumArt"
            :history="history"
            @show-image="showImage"
        />
    </modal>
</template>

<script setup>
import SongHistory from './SongHistory';
import Modal from "~/components/Common/Modal.vue";
import {ref} from "vue";

const props = defineProps({
    history: {
        type: Object,
        default: () => {
            return {};
        }
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
});

const emit = defineEmits(['showImage']);

const $modal = ref(); // Modal

const open = () => {
    $modal.value.show();
}

const showImage = (url) => {
    emit('showImage', url);
}

defineExpose({
    open
});
</script>
