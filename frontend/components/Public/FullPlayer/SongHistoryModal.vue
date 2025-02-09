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
import SongHistory from "~/components/Public/FullPlayer/SongHistory.vue";
import Modal from "~/components/Common/Modal.vue";
import {useTemplateRef} from "vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {ApiNowPlayingSongHistory} from "~/entities/ApiInterfaces.ts";

withDefaults(
    defineProps<{
        history: ApiNowPlayingSongHistory[],
        showAlbumArt?: boolean,
    }>(),
    {
        showAlbumArt: true
    }
);

const $modal = useTemplateRef('$modal');
const {show: open} = useHasModal($modal);

defineExpose({
    open
});
</script>
