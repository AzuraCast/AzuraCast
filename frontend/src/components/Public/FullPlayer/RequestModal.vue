<template>
    <modal
        id="request_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Request a Song')"
        hide-footer
    >
        <song-request
            v-bind="props"
            @submitted="hide"
        />
    </modal>
</template>

<script setup lang="ts">
import SongRequest from '../Requests.vue';
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import requestsProps from "~/components/Public/Requests/requestsProps.ts";

const props = defineProps({
    ...requestsProps
});

const $modal = ref<ModalTemplateRef>(null);
const {show: open, hide} = useHasModal($modal);

defineExpose({
    open
});
</script>
