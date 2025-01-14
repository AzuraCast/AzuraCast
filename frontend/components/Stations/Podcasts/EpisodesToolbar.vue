<template>
    <div class="card-body py-1 buttons d-flex align-items-center">
        <span>
            {{ $gettext('With selected:') }}
        </span>

        <button
            type="button"
            class="btn btn-sm btn-warning"
            :disabled="!hasSelectedItems"
            @click="doEdit"
        >
            <icon :icon="IconEdit" />
            <span>
                {{ $gettext('Edit') }}
            </span>
        </button>

        <button
            v-if="podcastIsManual"
            type="button"
            class="btn btn-sm btn-danger"
            :disabled="!hasSelectedItems"
            @click="doDelete"
        >
            <icon :icon="IconDelete" />
            <span>
                {{ $gettext('Delete') }}
            </span>
        </button>
    </div>
</template>

<script setup lang="ts">
import Icon from '~/components/Common/Icon.vue';
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {IconDelete, IconEdit} from "~/components/Common/icons";
import {computed, toRef} from "vue";
import useHandlePodcastBatchResponse from "~/components/Stations/Podcasts/useHandlePodcastBatchResponse.ts";
import {map} from "lodash";
import {useDialog} from "~/functions/useDialog.ts";

const props = withDefaults(
    defineProps<{
        batchUrl: string,
        selectedItems: Array<any>,
        podcastIsManual: boolean,
    }>(),
    {
        podcastIsManual: true,
    }
);

const emit = defineEmits(['relist', 'batch-edit']);

const {$gettext} = useTranslate();
const {axios} = useAxios();

const selectedItems = toRef(props, 'selectedItems');

const hasSelectedItems = computed(() => {
    return selectedItems.value.length > 0;
});

const {handleBatchResponse} = useHandlePodcastBatchResponse();

const doBatch = (action, successMessage, errorMessage) => {
    axios.put(props.batchUrl, {
        'do': action,
        'episodes': map(props.selectedItems, 'id')
    }).then(({data}) => {
        handleBatchResponse(data, successMessage, errorMessage);
        emit('relist');
    });
};

const {confirmDelete} = useDialog();

const doDelete = () => {
    confirmDelete({
        title: $gettext(
            'Delete %{num} episodes?',
            {
                num: String(props.selectedItems.length)
            }
        ),
    }).then((result) => {
        if (result.value) {
            doBatch(
                'delete',
                $gettext('Episodes removed:'),
                $gettext('Error removing episodes:')
            );
        }
    });
};

const doEdit = () => {
    emit('batch-edit');
}
</script>
