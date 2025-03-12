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
import SongRequest, {RequestsProps} from "~/components/Public/Requests.vue";
import {useTemplateRef} from "vue";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";

defineOptions({
    inheritAttrs: false
});

const props = defineProps<RequestsProps>();

const $modal = useTemplateRef('$modal');

const {show: open, hide} = useHasModal($modal);

defineExpose({
    open
});
</script>
