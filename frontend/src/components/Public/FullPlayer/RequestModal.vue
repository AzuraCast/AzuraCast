<template>
    <modal
        id="request_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Request a Song')"
        hide-footer
    >
        <song-request
            :show-album-art="showAlbumArt"
            :request-list-uri="requestListUri"
            :custom-fields="customFields"
            @submitted="hide"
        />
    </modal>
</template>

<script setup lang="ts">
import SongRequest from '../Requests.vue';
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    requestListUri: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    customFields: {
        type: Array,
        required: false,
        default: () => []
    }
});

const $modal = ref<ModalTemplateRef>(null);
const {show: open, hide} = useHasModal($modal);

defineExpose({
    open
});
</script>
