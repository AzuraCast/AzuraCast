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
                    <icon :icon="IconChevronLeft" />
                    <span>
                        {{ $gettext('Back') }}
                    </span>
                </button>
            </div>
            <div class="flex-fill m-2 m-md-0 text-end">
                <h6 class="m-0">
                    {{ destinationDirectory }}
                </h6>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <data-table
                    id="station_media"
                    ref="$datatable"
                    :show-toolbar="false"
                    :selectable="false"
                    :fields="fields"
                    :api-url="listDirectoriesUrl"
                    :request-config="requestConfig"
                >
                    <template #cell(directory)="row">
                        <div class="is_dir d-flex align-items-center">
                            <span class="file-icon me-2">
                                <icon :icon="IconFolder" />
                            </span>

                            <a
                                href="#"
                                @click.prevent="enterDirectory(row.item.path)"
                            >
                                {{ row.item.name }}
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
            >
                {{ $gettext('Move to Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from '~/components/Common/DataTable.vue';
import {forEach} from 'lodash';
import Icon from '~/components/Common/Icon.vue';
import {computed, h, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {IconChevronLeft, IconFolder} from "~/components/Common/icons";
import {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    selectedItems: {
        type: Object,
        required: true
    },
    currentDirectory: {
        type: String,
        required: true
    },
    batchUrl: {
        type: String,
        required: true
    },
    listDirectoriesUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const destinationDirectory = ref('');
const dirHistory = ref([]);

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'directory', label: $gettext('Directory'), sortable: false}
];

const langHeader = computed(() => {
    return $gettext(
        'Move %{ num } File(s) to',
        {num: props.selectedItems.all.length}
    );
});

const $modal = ref<ModalTemplateRef>(null);
const {show: open, hide} = useHasModal($modal);

const onHidden = () => {
    dirHistory.value = [];
    destinationDirectory.value = '';
}

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doMove = () => {
    (props.selectedItems.all.length) && axios.put(props.batchUrl, {
            'do': 'move',
            'currentDirectory': props.currentDirectory,
            'directory': destinationDirectory.value,
            'files': props.selectedItems.files,
            'dirs': props.selectedItems.directories
    }).then(() => {
        const notifyMessage = $gettext('Files moved:');
        const itemNameNodes = [];
        forEach(props.selectedItems.all, (item) => {
            itemNameNodes.push(h('div', {}, item.path_short));
        });

        notifySuccess(itemNameNodes, {
            title: notifyMessage
        });
    }).finally(() => {
        hide();
        emit('relist');
    });
};

const $datatable = ref<DataTableTemplateRef>(null);

const enterDirectory = (path) => {
    dirHistory.value.push(path);
    destinationDirectory.value = path;

    $datatable.value?.refresh();
};

const pageBack = () => {
    dirHistory.value.pop();

    let newDirectory = dirHistory.value.slice(-1)[0];
    if (typeof newDirectory === 'undefined' || null === newDirectory) {
        newDirectory = '';
    }

    destinationDirectory.value = newDirectory;
    $datatable.value?.refresh();
};

const requestConfig = (config) => {
    config.params.currentDirectory = destinationDirectory.value;
    return config;
};

defineExpose({
    open
});
</script>
