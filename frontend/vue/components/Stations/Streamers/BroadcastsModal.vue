<template>
    <modal
        id="streamer_broadcasts"
        ref="$modal"
        size="lg"
        centered
        :title="$gettext('Streamer Broadcasts')"
    >
        <template v-if="listUrl">
            <inline-player
                ref="$player"
                class="bg-primary rounded mb-2 p-3"
            />

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
                            <icon icon="cloud_download" />
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
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable.vue';
import formatFileSize from '~/functions/formatFileSize';
import InlinePlayer from '~/components/InlinePlayer';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import '~/vendor/sweetalert';
import {useAzuraCast} from "~/vendor/azuracast";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useLuxon} from "~/vendor/luxon";

const listUrl = ref(null);

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();
const {DateTime} = useLuxon();

const fields = [
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
        formatter: (value, key, item) => {
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

const $datatable = ref(); // Template Ref

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Broadcast?')
    }).then((result) => {
        if (result.value) {
            axios.delete(url).then((resp) => {
                notifySuccess(resp.data.message);
                $datatable.value.refresh();
            });

            $datatable.value.refresh();
        }
    });
};

const $modal = ref(); // Template Ref

const open = (newListUrl) => {
    listUrl.value = newListUrl;
    $modal.value.show();
};

const $player = ref(); // Template Ref

const close = () => {
    $player.value.stop();

    listUrl.value = null;

    $modal.value.hide();
};

defineExpose({
    open
});
</script>
