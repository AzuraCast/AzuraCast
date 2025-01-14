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
        />
    </modal>
</template>

<script setup lang="ts">
import SongHistory from './SongHistory.vue';
import Modal from "~/components/Common/Modal.vue";
import {ref} from "vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import {ApiNowPlayingSongHistory} from "~/entities/ApiInterfaces.ts";

const props = withDefaults(
    defineProps<{
        history: ApiNowPlayingSongHistory[],
        showAlbumArt?: boolean,
    }>(),
    {
        showAlbumArt: true
    }
);

const $modal = ref<ModalTemplateRef>(null);
const {show: open} = useHasModal($modal);

defineExpose({
    open
});
</script>
