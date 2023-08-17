<template>
    <section
        class="card"
        role="region"
    >
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Song Requests') }}
            </h2>
        </div>

        <div class="card-body">
            <nav
                class="nav nav-tabs"
                role="tablist"
            >
                <div
                    v-for="tab in tabs"
                    :key="tab.type"
                    class="nav-item"
                    role="presentation"
                >
                    <button
                        class="nav-link"
                        :class="(activeType === tab.type) ? 'active' : ''"
                        type="button"
                        role="tab"
                        @click="setType(tab.type)"
                    >
                        {{ tab.title }}
                    </button>
                </div>
            </nav>
        </div>

        <div
            v-if="activeType === 'pending'"
            class="card-body"
        >
            <button
                type="button"
                class="btn btn-danger"
                @click="doClear()"
            >
                <icon icon="remove" />
                <span>
                    {{ $gettext('Clear Pending Requests') }}
                </span>
            </button>
        </div>

        <data-table
            id="station_queue"
            ref="$datatable"
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
                <button
                    v-if="row.item.played_at === 0"
                    type="button"
                    class="btn btn-sm btn-danger"
                    @click="doDelete(row.item.links.delete)"
                >
                    {{ $gettext('Delete') }}
                </button>
            </template>
        </data-table>
    </section>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import Icon from "~/components/Common/Icon";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {computed, nextTick, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useLuxon} from "~/vendor/luxon";
import {getStationApiUrl} from "~/router";

const listUrl = getStationApiUrl('/reports/requests');
const clearUrl = getStationApiUrl('/reports/requests/clear');

const activeType = ref('pending');

const listUrlForType = computed(() => {
    return listUrl.value + '?type=' + activeType.value;
});

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

const $datatable = ref(); // Template Ref

const relist = () => {
    $datatable.value.refresh();
};

const setType = (type) => {
    activeType.value = type;
    nextTick(relist);
};

const {timeConfig} = useAzuraCast();
const {timezone} = useAzuraCastStation();

const {DateTime} = useLuxon();

const formatTime = (time) => {
    return DateTime.fromSeconds(time).setZone(timezone).toLocaleString(
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
                axios.post(clearUrl.value)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>
