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
            @submitted="doClose"
        />
    </modal>
</template>

<script setup>
import SongRequest from '../Requests';
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";

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

const $modal = ref(); // Modal

const doClose = () => {
    $modal.value?.hide();
};

const open = () => {
    $modal.value.show();
}

defineExpose({
    open
});
</script>
