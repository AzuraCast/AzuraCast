<template>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header text-bg-primary">
                    <div class="d-lg-flex align-items-center">
                        <div class="flex-fill my-0">
                            <h2 class="card-title">
                                {{ $gettext('Listeners') }}
                            </h2>
                        </div>
                        <div class="flex-shrink buttons mt-2 mt-lg-0">
                            <a
                                id="btn-export"
                                class="btn btn-dark"
                                :href="exportUrl"
                                target="_blank"
                            >
                                <icon :icon="IconDownload" />
                                <span>
                                    {{ $gettext('Download CSV') }}
                                </span>
                            </a>
                        </div>
                        <div
                            v-if="!isLive"
                            class="flex-shrink buttons ms-lg-2 mt-2 mt-lg-0"
                        >
                            <date-range-dropdown
                                v-model="dateRange"
                                time-picker
                                :min-date="minDate"
                                :max-date="maxDate"
                                :tz="timezone"
                            />
                        </div>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <nav
                        class="nav nav-tabs"
                        role="tablist"
                    >
                        <div
                            class="nav-item"
                            role="presentation"
                        >
                            <button
                                class="nav-link"
                                :class="(isLive) ? 'active' : ''"
                                type="button"
                                role="tab"
                                @click="setIsLive(true)"
                            >
                                {{ $gettext('Live Listeners') }}
                            </button>
                        </div>
                        <div
                            class="nav-item"
                            role="presentation"
                        >
                            <button
                                class="nav-link"
                                :class="(!isLive) ? 'active' : ''"
                                type="button"
                                role="tab"
                                @click="setIsLive(false)"
                            >
                                {{ $gettext('Listener History') }}
                            </button>
                        </div>
                    </nav>
                </div>

                <div id="map">
                    <StationReportsListenersMap
                        :listeners="filteredListeners"
                    />
                </div>
                <div>
                    <div class="card-body">
                        <div class="row row-cols-md-auto align-items-center">
                            <div class="col-12 text-start text-md-end h5">
                                {{ $gettext('Unique Listeners') }}
                                <br>
                                <small>
                                    {{ $gettext('for selected period') }}
                                </small>
                            </div>
                            <div class="col-12 h3">
                                {{ listeners.length }}
                            </div>
                            <div class="col-12 text-start text-md-end h5">
                                {{ $gettext('Total Listener Hours') }}
                                <br>
                                <small>
                                    {{ $gettext('for selected period') }}
                                </small>
                            </div>
                            <div class="col-12 h3">
                                {{ totalListenerHours }}
                            </div>
                            <div class="col-12">
                                <listener-filters-bar v-model:filters="filters" />
                            </div>
                        </div>
                    </div>

                    <data-table
                        id="station_listeners"
                        ref="$datatable"
                        paginated
                        handle-client-side
                        :fields="fields"
                        :items="filteredListeners"
                        select-fields
                        @refresh-clicked="updateListeners()"
                    >
                        <template #cell(user_agent)="row">
                            <div>
                                <span v-if="row.item.is_mobile">
                                    <icon :icon="IconSmartphone" />
                                    <span class="visually-hidden">
                                        {{ $gettext('Mobile') }}
                                    </span>
                                </span>
                                <span v-else>
                                    <icon :icon="IconDesktopWindows" />
                                    <span class="visually-hidden">
                                        {{ $gettext('Desktop') }}
                                    </span>
                                </span>

                                {{ row.item.user_agent }}
                            </div>
                            <div v-if="row.item.device.client">
                                <small>{{ row.item.device.client }}</small>
                            </div>
                        </template>
                        <template #cell(stream)="row">
                            <span v-if="row.item.mount_name === ''">
                                {{ $gettext('Unknown') }}
                            </span>
                            <span v-else>
                                {{ row.item.mount_name }}<br>
                                <small v-if="row.item.mount_is_local">
                                    {{ $gettext('Local') }}
                                </small>
                                <small v-else>
                                    {{ $gettext('Remote') }}
                                </small>
                            </span>
                        </template>
                        <template #cell(location)="row">
                            <span v-if="row.item.location.description">
                                {{ row.item.location.description }}
                            </span>
                            <span v-else>
                                {{ $gettext('Unknown') }}
                            </span>
                        </template>
                    </data-table>
                </div>
                <div
                    class="card-body card-padding-sm text-muted"
                    v-html="attribution"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import StationReportsListenersMap from "./Listeners/Map.vue";
import Icon from "~/components/Common/Icon.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown.vue";
import {computed, ComputedRef, nextTick, onMounted, Ref, ref, ShallowRef, shallowRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {useLuxon} from "~/vendor/luxon";
import {getStationApiUrl} from "~/router";
import {IconDesktopWindows, IconDownload, IconSmartphone} from "~/components/Common/icons";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import {ListenerFilters, ListenerTypeFilter} from "~/components/Stations/Reports/Listeners/listenerFilters.ts";
import {filter} from "lodash";
import formatTime from "~/functions/formatTime.ts";
import ListenerFiltersBar from "./Listeners/FiltersBar.vue";
import {ApiListener} from "~/entities/ApiInterfaces.ts";

const props = defineProps({
    attribution: {
        type: String,
        required: true
    }
});

const apiUrl = getStationApiUrl('/listeners');

const isLive = ref<boolean>(true);
const listeners: ShallowRef<ApiListener[]> = shallowRef([]);

const {timezone} = useAzuraCastStation();
const {timeConfig} = useAzuraCast();

const {DateTime} = useLuxon();
const nowTz = DateTime.now().setZone(timezone);

const minDate = nowTz.minus({years: 5}).toJSDate();
const maxDate = nowTz.plus({days: 5}).toJSDate();

const dateRange = ref({
    startDate: nowTz.minus({days: 1}).toJSDate(),
    endDate: nowTz.toJSDate()
});

const filters: Ref<ListenerFilters> = ref({
    minLength: null,
    maxLength: null,
    type: ListenerTypeFilter.All,
});

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {
        key: 'ip', label: $gettext('IP'), sortable: false,
        selectable: true,
        visible: true
    },
    {
        key: 'connected_time',
        label: $gettext('Time'),
        sortable: true,
        formatter: (_col, _key, item) => {
            return formatTime(item.connected_time)
        },
        selectable: true,
        visible: true
    },
    {
        key: 'connected_time_sec',
        label: $gettext('Time (sec)'),
        sortable: false,
        formatter: (_col, _key, item) => {
            return item.connected_time;
        },
        selectable: true,
        visible: false
    },
    {
        key: 'connected_on',
        label: $gettext('Start Time'),
        sortable: true,
        formatter: (_col, _key, item) => {
            return DateTime.fromSeconds(
                item.connected_on,
                {zone: timezone}
            ).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...timeConfig}
            );
        },
        selectable: true,
        visible: false
    },
    {
        key: 'connected_until',
        label: $gettext('End Time'),
        sortable: true,
        formatter: (_col, _key, item) => {
            return DateTime.fromSeconds(
                item.connected_until,
                {zone: timezone}
            ).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...timeConfig}
            );
        },
        selectable: true,
        visible: false
    },
    {
        key: 'user_agent',
        isRowHeader: true,
        label: $gettext('User Agent'),
        sortable: true,
        selectable: true,
        visible: true
    },
    {
        key: 'stream',
        label: $gettext('Stream'),
        sortable: true,
        selectable: true,
        visible: true
    },
    {
        key: 'location',
        label: $gettext('Location'),
        sortable: true,
        selectable: true,
        visible: true
    }
];

const exportUrl = computed(() => {
    const exportUrl = new URL(apiUrl.value, document.location.href);
    const exportUrlParams = exportUrl.searchParams;
    exportUrlParams.set('format', 'csv');

    if (!isLive.value) {
        exportUrlParams.set('start', DateTime.fromJSDate(dateRange.value.startDate).toISO());
        exportUrlParams.set('end', DateTime.fromJSDate(dateRange.value.endDate).toISO());
    }

    return exportUrl.toString();
});

const totalListenerHours = computed(() => {
    let tlh_seconds = 0;
    filteredListeners.value.forEach(function (listener) {
        tlh_seconds += listener.connected_time;
    });

    const tlh_hours = tlh_seconds / 3600;
    return Math.round((tlh_hours + 0.00001) * 100) / 100;
});

const {axios} = useAxios();

const $datatable = ref<DataTableTemplateRef>(null);
const {navigate} = useHasDatatable($datatable);

const hasFilters: ComputedRef<boolean> = computed(() => {
    return null !== filters.value.minLength
        || null !== filters.value.maxLength
        || ListenerTypeFilter.All !== filters.value.type;
});

const filteredListeners: ComputedRef<ApiListener[]> = computed(() => {
    if (!hasFilters.value) {
        return listeners.value;
    }

    return filter(
        listeners.value,
        (row: ApiListener) => {
            const connectedTime: number = row.connected_time;
            if (null !== filters.value.minLength && connectedTime < filters.value.minLength) {
                return false;
            }
            if (null !== filters.value.maxLength && connectedTime > filters.value.maxLength) {
                return false;
            }
            if (ListenerTypeFilter.All !== filters.value.type) {
                if (ListenerTypeFilter.Mobile === filters.value.type && !row.device.is_mobile) {
                    return false;
                } else if (ListenerTypeFilter.Desktop === filters.value.type && row.device.is_mobile) {
                    return false;
                }
            }

            return true;
        }
    );
});

const updateListeners = () => {
    const params: {
        [key: string]: any
    } = {};

    if (!isLive.value) {
        params.start = DateTime.fromJSDate(dateRange.value.startDate).toISO();
        params.end = DateTime.fromJSDate(dateRange.value.endDate).toISO();
    }

    axios.get(apiUrl.value, {params: params}).then((resp) => {
        listeners.value = resp.data;
        navigate();

        if (isLive.value) {
            setTimeout(updateListeners, (!document.hidden) ? 15000 : 30000);
        }
    }).catch((error) => {
        if (isLive.value && (!error.response || error.response.data.code !== 403)) {
            setTimeout(updateListeners, (!document.hidden) ? 30000 : 120000);
        }
    });
};

watch(dateRange, updateListeners);

onMounted(updateListeners);

const setIsLive = (newValue: boolean) => {
    isLive.value = newValue;
    nextTick(updateListeners);
};
</script>
