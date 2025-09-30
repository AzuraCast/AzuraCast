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
            <icon-ic-edit/>

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
            <icon-ic-delete/>

            <span>
                {{ $gettext('Delete') }}
            </span>
        </button>
    </div>
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {computed, toRef} from "vue";
import useHandlePodcastBatchResponse from "~/components/Stations/Podcasts/useHandlePodcastBatchResponse.ts";
import {useDialog} from "~/components/Common/Dialogs/useDialog.ts";
import {ApiPodcastEpisode} from "~/entities/ApiInterfaces.ts";
import IconIcDelete from "~icons/ic/baseline-delete";
import IconIcEdit from "~icons/ic/baseline-edit";

const props = withDefaults(
    defineProps<{
        batchUrl: string,
        selectedItems: ApiPodcastEpisode[],
        podcastIsManual: boolean,
    }>(),
    {
        podcastIsManual: true,
    }
);

const emit = defineEmits<{
    (e: 'relist'): void,
    (e: 'batch-edit'): void
}>();

const {$gettext} = useTranslate();
const {axios} = useAxios();

const selectedItems = toRef(props, 'selectedItems');

const hasSelectedItems = computed(() => {
    return selectedItems.value.length > 0;
});

const {handleBatchResponse} = useHandlePodcastBatchResponse();

const doBatch = async (action: string, successMessage: string, errorMessage: string) => {
    const {data} = await axios.put(props.batchUrl, {
        'do': action,
        'episodes': props.selectedItems.map((row) => row.id)
    });

    handleBatchResponse(data, successMessage, errorMessage);
    emit('relist');
};

const {confirmDelete} = useDialog();

const doDelete = async () => {
    const {value} = await confirmDelete({
        title: $gettext(
            'Delete %{num} episodes?',
            {
                num: String(props.selectedItems.length)
            }
        ),
    });

    if (!value) {
        return;
    }

    await doBatch(
        'delete',
        $gettext('Episodes removed:'),
        $gettext('Error removing episodes:')
    );
};

const doEdit = () => {
    emit('batch-edit');
}
</script>
