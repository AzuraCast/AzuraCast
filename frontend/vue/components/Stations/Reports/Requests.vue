<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Song Requests') }}
            </h2>
        </b-card-header>
        <b-tabs
            pills
            card
        >
            <b-tab
                v-for="tab in tabs"
                :key="tab.type"
                :active="activeType === tab.type"
                :title="tab.title"
                no-body
                @click="setType(tab.type)"
            />
        </b-tabs>

        <div
            v-if="activeType === 'pending'"
            class="card-actions"
        >
            <b-button
                variant="outline-danger"
                @click="doClear()"
            >
                <icon icon="remove" />
                {{ $gettext('Clear Pending Requests') }}
            </b-button>
        </div>

        <data-table
            id="station_queue"
            ref="datatable"
            :fields="fields"
            :api-url="listUrlForType"
        >
            <template #cell(timestamp)="row">
                {{ formatTime(row.item.timestamp) }}
            </template>
            <template #cell(played_at)="row">
                <span v-if="row.item.played_at === 0">
                    {{ $gettext('Not Played') }}
                </span>
                <span v-else>
                    {{ formatTime(row.item.played_at) }}
                </span>
            </template>
            <template #cell(song_title)="row">
                <div v-if="row.item.track.title">
                    <b>{{ row.item.track.title }}</b><br>
                    {{ row.item.track.artist }}
                </div>
                <div v-else>
                    {{ row.item.track.text }}
                </div>
            </template>
            <template #cell(ip)="row">
                {{ row.item.ip }}
            </template>
            <template #cell(actions)="row">
                <b-button-group>
                    <b-button
                        v-if="row.item.played_at === 0"
                        size="sm"
                        variant="danger"
                        @click.prevent="doDelete(row.item.links.delete)"
                    >
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </b-card>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import Icon from "~/components/Common/Icon";
import {DateTime} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    clearUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});

const activeType = ref('pending');

const {$gettext} = useTranslate();

const fields = [
    {key: 'timestamp', label: $gettext('Date Requested'), sortable: false},
    {key: 'played_at', label: $gettext('Date Played'), sortable: false},
    {key: 'song_title', isRowHeader: true, label: $gettext('Song Title'), sortable: false},
    {key: 'ip', label: $gettext('Requester IP'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false}
];

const tabs = [
    {
        type: 'pending',
        title: $gettext('Pending Requests')
    },
    {
        type: 'history',
        title: $gettext('Request History')
    }
];

const listUrlForType = computed(() => {
    return props.listUrl + '?type=' + activeType.value;
});

const $datatable = ref(); // Template Ref

const relist = () => {
    $datatable.value.refresh();
};

const setType = (type) => {
    activeType.value = type;
    relist();
};

const formatTime = (time) => {
    const {timeConfig} = useAzuraCast();

    return DateTime.fromSeconds(time).setZone(props.stationTimeZone).toLocaleString(
        {...DateTime.DATETIME_MED, ...timeConfig}
    );
};

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Request?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};

const doClear = () => {
    confirmDelete({
        title: $gettext('Clear All Pending Requests?'),
        confirmButtonText: $gettext('Clear'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.post(props.clearUrl)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>
