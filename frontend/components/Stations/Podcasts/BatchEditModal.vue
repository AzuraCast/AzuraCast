<template>
    <modal
        id="batch_edit_modal"
        ref="$modal"
        centered
        size="xl"
        :title="$gettext('Bulk Edit Episodes')"
        @hidden="onHidden"
    >
        <form @submit.prevent="doBatchEdit">
            <loading :loading="isLoading">
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-striped table-hover">
                        <colgroup>
                            <col>
                            <col>
                            <col style="width: 8rem;">
                            <col style="width: 11rem;">
                            <col style="width: 11rem;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>{{ $gettext('Episode') }}</th>
                                <th>{{ $gettext('Publish At') }}</th>
                                <th>{{ $gettext('Explicit') }}</th>
                                <th>{{ $gettext('Season Number') }}</th>
                                <th>{{ $gettext('Episode Number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <batch-edit-row
                                v-for="(row, index) in rows"
                                :key="row.id"
                                v-model:row="rows[index]"
                                :index="index"
                            />
                        </tbody>
                    </table>
                </div>
            </loading>

            <invisible-submit-button />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doBatchEdit"
            >
                {{ $gettext('Save Changes') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {computed, ref, useTemplateRef} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import Loading from "~/components/Common/Loading.vue";
import useHandlePodcastBatchResponse from "~/components/Stations/Podcasts/useHandlePodcastBatchResponse.ts";
import {map} from "lodash";
import {useTranslate} from "~/vendor/gettext.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import BatchEditRow from "~/components/Stations/Podcasts/BatchEditRow.vue";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiPodcastBatchResult, ApiPodcastEpisode} from "~/entities/ApiInterfaces.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";

const props = defineProps<{
    id: string,
    batchUrl: string,
    selectedItems: Array<any>,
}>();

const emit = defineEmits<HasRelistEmit>();

const {v$, resetForm, ifValid} = useVuelidateOnForm();

const $modal = useTemplateRef('$modal');
const {show: showModal, hide} = useHasModal($modal);

const {axios} = useAxios();

const blankRow = {
    id: null,
    title: null,
    publish_at: null,
    explicit: false,
    season_number: null,
    episode_number: null
};

const isModalOpen = ref(false);

const {data: rows, isLoading} = useQuery<ApiPodcastEpisode[]>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationPodcasts
    ], [
        computed(() => props.id),
        'batch',
        computed(() => props.selectedItems),
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.put<ApiPodcastBatchResult>(props.batchUrl, {
            'do': 'list',
            'episodes': map(props.selectedItems, 'id'),
        }, {signal});

        return map(
            data.records ?? [],
            (row) => mergeExisting(blankRow, row)
        );
    },
    enabled: isModalOpen,
});

const show = () => {
    isModalOpen.value = true;
    showModal();
};

const onHidden = () => {
    rows.value = [];
    isModalOpen.value = false;
    resetForm();
}

const {$gettext} = useTranslate();
const {handleBatchResponse} = useHandlePodcastBatchResponse();

const doBatchEdit = () => {
    ifValid(() => {
        void axios.put(props.batchUrl, {
            'do': 'edit',
            'episodes': map(props.selectedItems, 'id'),
            'records': rows.value
        }).then(({data}) => {
            handleBatchResponse(
                data,
                $gettext('Episodes updated:'),
                $gettext('Error updating episodes:')
            );

            hide();
            emit('relist');
        });
    });
};

defineExpose({
    show,
    hide
});
</script>
