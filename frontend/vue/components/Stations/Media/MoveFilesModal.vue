<template>
    <modal
        id="move_file"
        ref="$modal"
        size="xl"
        centered
        :title="langHeader"
    >
        <div class="row mb-3 align-items-center">
            <div class="col-md-6">
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    :disabled="dirHistory.length === 0"
                    @click="pageBack"
                >
                    <icon icon="chevron_left" />
                    <span>
                        {{ $gettext('Back') }}
                    </span>
                </button>
            </div>
            <div class="col-md-6 text-end">
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
                        <div class="is_dir">
                            <span class="file-icon">
                                <icon icon="folder" />
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
                @click="close"
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

<script setup>
import DataTable from '~/components/Common/DataTable.vue';
import {forEach} from 'lodash';
import Icon from '~/components/Common/Icon';
import {computed, h, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";

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

const fields = [
    {key: 'directory', label: $gettext('Directory'), sortable: false}
];

const langHeader = computed(() => {
    return $gettext(
        'Move %{ num } File(s) to',
        {num: props.selectedItems.all.length}
    );
});

const $modal = ref(); // Template Ref

const close = () => {
    dirHistory.value = [];
    destinationDirectory.value = '';

    $modal.value.hide();
};

const open = () => {
    $modal.value.show();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doMove = () => {
    (props.selectedItems.all.length) && wrapWithLoading(
        axios.put(props.batchUrl, {
            'do': 'move',
            'currentDirectory': props.currentDirectory,
            'directory': destinationDirectory.value,
            'files': props.selectedItems.files,
            'dirs': props.selectedItems.directories
        })
    ).then(() => {
        const notifyMessage = $gettext('Files moved:');
        const itemNameNodes = [];
        forEach(props.selectedItems.all, (item) => {
            itemNameNodes.push(h('div', {}, item.path_short));
        });

        notifySuccess(itemNameNodes, {
            title: notifyMessage
        });
    }).finally(() => {
        close();
        emit('relist');
    });
};

const $datatable = ref(); // Template Ref

const enterDirectory = (path) => {
    dirHistory.value.push(path);
    destinationDirectory.value = path;

    $datatable.value.refresh();
};

const pageBack = () => {
    dirHistory.value.pop();

    let newDirectory = dirHistory.value.slice(-1)[0];
    if (typeof newDirectory === 'undefined' || null === newDirectory) {
        newDirectory = '';
    }

    destinationDirectory.value = newDirectory;
    $datatable.value.refresh();
};

const requestConfig = (config) => {
    config.params.currentDirectory = destinationDirectory.value;
    return config;
};

defineExpose({
    open
});
</script>
