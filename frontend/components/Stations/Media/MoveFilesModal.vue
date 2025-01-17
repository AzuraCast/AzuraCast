<template>
    <modal
        id="move_file"
        ref="$modal"
        size="xl"
        centered
        :title="langHeader"
        @hidden="onHidden()"
    >
        <div class="d-flex flex-column flex-md-row mb-3 align-items-center">
            <div class="flex-shrink m-2 m-md-0 me-md-3">
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    :disabled="dirHistory.length === 0"
                    @click="pageBack"
                >
                    <icon :icon="IconChevronLeft"/>
                    <span>
                        {{ $gettext('Back') }}
                    </span>
                </button>
            </div>
            <div class="flex-fill m-2 m-md-0 text-end">
                <h5 class="m-0">
                    <small>{{ $gettext('Selected directory:') }}</small><br>
                    <template v-if="destinationDirectory">
                        {{ destinationDirectory }}
                    </template>
                    <template v-else>
                        {{ $gettext('Base Directory') }}
                    </template>
                </h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <data-table
                    id="station_media"
                    ref="$datatable"
                    show-toolbar
                    paginated
                    :fields="fields"
                    :items="directories"
                    :loading="isLoading"
                    handle-client-side
                    @refresh-clicked="reload()"
                >
                    <template #cell(name)="{item}">
                        <div class="is_dir d-flex align-items-center">
                            <span class="file-icon me-2">
                                <icon :icon="IconFolder"/>
                            </span>

                            <a
                                href="#"
                                @click.prevent="enterDirectory(item.path)"
                            >
                                {{ item.name }}
                            </a>
                        </div>
                    </template>
                </data-table>
            </div>
        </div>
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
                class="btn btn-primary"
                @click="doMove"
                :disabled="props.selectedItems.all.length === 0"
            >
                {{ $gettext('Move to Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from '~/components/Common/DataTable.vue';
import Icon from '~/components/Common/Icon.vue';
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {IconChevronLeft, IconFolder} from "~/components/Common/icons";
import {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import useHandleBatchResponse from "~/components/Stations/Media/useHandleBatchResponse.ts";
import {useAsyncState} from "@vueuse/core";
import {MediaSelectedItems} from "~/components/Stations/Media.vue";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";

const props = defineProps<{
    selectedItems: MediaSelectedItems,
    currentDirectory: string,
    batchUrl: string,
    listDirectoriesUrl: string,
}>();

const emit = defineEmits<HasRelistEmit>();

const destinationDirectory = ref('');
const dirHistory = ref([]);

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'name', label: $gettext('Directory'), sortable: true}
];

const langHeader = computed(() => {
    return $gettext(
        'Move %{num} File(s) to',
        {num: String(props.selectedItems.all.length)}
    );
});

const $modal = ref<ModalTemplateRef>(null);
const {show, hide} = useHasModal($modal);

const onHidden = () => {
    dirHistory.value = [];
    destinationDirectory.value = '';
}

const {axios} = useAxios();

const {handleBatchResponse} = useHandleBatchResponse();

const {state: directories, execute: reload, isLoading} = useAsyncState(
    () => axios.get(props.listDirectoriesUrl, {
        params: {
            currentDirectory: destinationDirectory.value
        }
    }).then((r) => r.data.rows),
    [],
    {
        immediate: false
    }
);

const doMove = () => {
    axios.put(props.batchUrl, {
        'do': 'move',
        'currentDirectory': props.currentDirectory,
        'directory': destinationDirectory.value,
        'files': props.selectedItems.files,
        'dirs': props.selectedItems.directories
    }).then(({data}) => {
        handleBatchResponse(
            data,
            $gettext('Files moved:'),
            $gettext('Error moving files:')
        );
    }).finally(() => {
        hide();
        emit('relist');
    });
};

const $datatable = ref<DataTableTemplateRef>(null);

const onDirChange = () => {
    reload();
    $datatable.value?.refresh();
}

const enterDirectory = (path) => {
    dirHistory.value.push(path);
    destinationDirectory.value = path;
    onDirChange();
};

const pageBack = () => {
    dirHistory.value.pop();

    let newDirectory = dirHistory.value.slice(-1)[0];
    if (typeof newDirectory === 'undefined' || null === newDirectory) {
        newDirectory = '';
    }

    destinationDirectory.value = newDirectory;
    onDirChange();
};

const open = () => {
    reload();
    show();
}

defineExpose({
    open
});
</script>
