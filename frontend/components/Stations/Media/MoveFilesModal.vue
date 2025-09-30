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
                    <icon-bi-chevron-left/>

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
                    id="station_media_directories"
                    show-toolbar
                    paginated
                    :fields="fields"
                    :provider="directoryItemProvider"
                >
                    <template #cell(name)="{item}">
                        <div class="is_dir d-flex align-items-center">
                            <span class="file-icon me-2">
                                <icon-ic-folder/>
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
                :disabled="props.selectedItems.all.length === 0"
                @click="doMove"
            >
                {{ $gettext('Move to Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {computed, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import useHandleBatchResponse from "~/components/Stations/Media/useHandleBatchResponse.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useQueryItemProvider} from "~/functions/dataTable/useQueryItemProvider.ts";
import {MediaSelectedItems} from "~/components/Stations/Media.vue";
import IconIcFolder from "~icons/ic/baseline-folder";
import IconBiChevronLeft from "~icons/bi/chevron-left";

const props = defineProps<{
    selectedItems: MediaSelectedItems,
    currentDirectory: string,
    batchUrl: string,
    listDirectoriesUrl: string,
}>();

const emit = defineEmits<HasRelistEmit>();

const destinationDirectory = ref<string>('');
const dirHistory = ref<string[]>([]);
const isModalVisible = ref(false);

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

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const onHidden = () => {
    dirHistory.value = [];
    destinationDirectory.value = '';
    isModalVisible.value = false;
}

const {axios} = useAxios();

const {handleBatchResponse} = useHandleBatchResponse();

const directoriesQuery = useQuery({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationMedia,
            'directories',
            destinationDirectory
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get(props.listDirectoriesUrl, {
            signal,
            params: {
                currentDirectory: destinationDirectory.value
            }
        });

        return data.rows;
    },
    staleTime: 60 * 1000,
    enabled: isModalVisible
});

const directoryItemProvider = useQueryItemProvider(directoriesQuery);

const doMove = async () => {
    try {
        const {data} = await axios.put(props.batchUrl, {
            'do': 'move',
            'currentDirectory': props.currentDirectory,
            'directory': destinationDirectory.value,
            'files': props.selectedItems.files,
            'dirs': props.selectedItems.directories
        });

        handleBatchResponse(
            data,
            $gettext('Files moved:'),
            $gettext('Error moving files:')
        );
    } finally {
        hide();
        emit('relist');
    }
};

const enterDirectory = (path: string) => {
    dirHistory.value.push(path);
    destinationDirectory.value = path;
};

const pageBack = () => {
    dirHistory.value.pop();

    let newDirectory = dirHistory.value.slice(-1)[0];
    if (typeof newDirectory === 'undefined' || null === newDirectory) {
        newDirectory = '';
    }

    destinationDirectory.value = newDirectory;
};

const open = () => {
    isModalVisible.value = true;
    show();
}

defineExpose({
    open
});
</script>
