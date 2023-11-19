<template>
    <modal
        id="streamer_broadcasts"
        ref="$modal"
        size="lg"
        centered
        :title="$gettext('Streamer Broadcasts')"
        @hidden="onHidden"
    >
        <template v-if="listUrl">
            <inline-player class="text-start bg-primary rounded mb-2 p-1" />

            <data-table
                id="station_streamer_broadcasts"
                ref="$datatable"
                :show-toolbar="false"
                :fields="fields"
                :api-url="listUrl"
            >
                <template #cell(download)="row">
                    <template v-if="row.item.recording?.links?.download">
                        <play-button :url="row.item.recording?.links?.download" />
                        <a
                            class="name btn p-0 ms-2"
                            :href="row.item.recording?.links?.download"
                            target="_blank"
                            :title="$gettext('Download')"
                        >
                            <icon :icon="IconDownload" />
                        </a>
                    </template>
                    <template v-else>
                    &nbsp;
                    </template>
                </template>
                <template #cell(actions)="row">
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="doDelete(row.item.links.delete)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </template>
            </data-table>
        </template>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from '~/components/Common/DataTable.vue';
import formatFileSize from '~/functions/formatFileSize';
import InlinePlayer from '~/components/InlinePlayer.vue';
import Icon from '~/components/Common/Icon.vue';
import PlayButton from "~/components/Common/PlayButton.vue";
import '~/vendor/sweetalert';
import {useAzuraCast} from "~/vendor/azuracast";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useLuxon} from "~/vendor/luxon";
import {IconDownload} from "~/components/Common/icons";
import {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import {usePlayerStore, useProvidePlayerStore} from "~/functions/usePlayerStore.ts";

const listUrl = ref(null);

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();
const {DateTime} = useLuxon();

const fields: DataTableField[] = [
    {
        key: 'download',
        label: ' ',
        sortable: false,
        class: 'shrink pe-3'
    },
    {
        key: 'timestampStart',
        label: $gettext('Start Time'),
        sortable: false,
        formatter: (value) => {
            return DateTime.fromSeconds(value).toLocaleString(
                {...DateTime.DATETIME_MED, ...timeConfig}
            );
        },
        class: 'ps-3'
    },
    {
        key: 'timestampEnd',
        label: $gettext('End Time'),
        sortable: false,
        formatter: (value) => {
            if (value === 0) {
                return $gettext('Live');
            }

            return DateTime.fromSeconds(value).toLocaleString(
                {...DateTime.DATETIME_MED, ...timeConfig}
            );
        }
    },
    {
        key: 'size',
        label: $gettext('Size'),
        sortable: false,
        formatter: (_value, _key, item) => {
            if (!item.recording?.size) {
                return '';
            }

            return formatFileSize(item.recording.size);
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const {confirmDelete} = useSweetAlert();
const {notifySuccess} = useNotify();
const {axios} = useAxios();

const $datatable = ref<DataTableTemplateRef>(null);

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Broadcast?')
    }).then((result) => {
        if (result.value) {
            axios.delete(url).then((resp) => {
                notifySuccess(resp.data.message);
                $datatable.value?.refresh();
            });

            $datatable.value?.refresh();
        }
    });
};

const $modal = ref<ModalTemplateRef>(null);
const {show, hide} = useHasModal($modal);

const open = (newListUrl) => {
    listUrl.value = newListUrl;
    show();
};

useProvidePlayerStore('broadcasts');

const {stop} = usePlayerStore();

const onHidden = () => {
    stop();
    listUrl.value = null;
};

defineExpose({
    open
});
</script>
