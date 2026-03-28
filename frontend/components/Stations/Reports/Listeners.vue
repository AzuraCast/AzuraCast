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
                                <icon-ic-cloud-download/>

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
                                :options="{
                                    timezone: timezone,
                                    timeConfig: {
                                        enableTimePicker: true
                                    },
                                    minDate: minDate,
                                    maxDate: maxDate,
                                }"
                                class="btn-dark"
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
                                {{ filteredListeners.length }}
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
                        ref="$dataTable"
                        paginated
                        :fields="fields"
                        :provider="listenersItemProvider"
                        select-fields
                    >
                        <!-- eslint-disable-next-line -->
                        <template #cell(device.client)="row">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 pe-2">
                                    <span v-if="row.item.device.is_bot">
                                        <icon-ic-router/>
                                        <span class="visually-hidden">
                                            {{ $gettext('Bot/Crawler') }}
                                        </span>
                                    </span>
                                    <span v-else-if="row.item.device.is_mobile">
                                        <icon-ic-smartphone/>
                                        <span class="visually-hidden">
                                            {{ $gettext('Mobile') }}
                                        </span>
                                    </span>
                                    <span v-else>
                                        <icon-ic-desktop-windows/>
                                        <span class="visually-hidden">
                                            {{ $gettext('Desktop') }}
                                        </span>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <div v-if="row.item.device.client">
                                        {{ row.item.device.client }}
                                    </div>
                                    <div class="small">
                                        {{ row.item.user_agent }}
                                    </div>
                                </div>
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
                    v-html="ipGeoAttribution"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import StationReportsListenersMap from "~/components/Stations/Reports/Listeners/Map.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import DateRangeDropdown, {DateRange} from "~/components/Common/DateRangeDropdown.vue";
import {computed, ComputedRef, Ref, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import useHasDatatable from "~/functions/useHasDatatable";
import {ListenerFilters, ListenerTypeFilters} from "~/components/Stations/Reports/Listeners/listenerFilters.ts";
import {filter} from "es-toolkit/compat";
import formatTime from "~/functions/formatTime.ts";
import ListenerFiltersBar from "~/components/Stations/Reports/Listeners/FiltersBar.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {useQuery, useQueryClient} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useClientItemProvider} from "~/functions/dataTable/useClientItemProvider.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";
import {ListenerRequired} from "~/entities/StationReports.ts";
import IconIcDesktopWindows from "~icons/ic/baseline-desktop-windows";
import IconIcCloudDownload from "~icons/ic/baseline-cloud-download";
import IconIcRouter from "~icons/ic/baseline-router";
import IconIcSmartphone from "~icons/ic/baseline-smartphone";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const apiUrl = getStationApiUrl('/listeners');

const isLive = ref<boolean>(true);

const {DateTime} = useLuxon();

const stationData = useStationData();
const {timezone, ipGeoAttribution} = toRefs(stationData);

const {
    now,
    formatTimestampAsDateTime
} = useStationDateTimeFormatter();

const nowTz = now();

const minDate = nowTz.minus({years: 5}).toJSDate();
const maxDate = nowTz.plus({days: 5}).toJSDate();

const dateRange = ref<DateRange>({
    startDate: nowTz.minus({days: 1}).toJSDate(),
    endDate: nowTz.toJSDate()
});

const filters: Ref<ListenerFilters> = ref({
    minLength: null,
    maxLength: null,
    type: ListenerTypeFilters.All,
});

const {$gettext} = useTranslate();

type Row = ListenerRequired;

const fields: DataTableField<Row>[] = [
    {
        key: 'ip', label: $gettext('IP'), sortable: false,
        selectable: true,
        visible: true
    },
    {
        key: 'connected_time',
        label: $gettext('Time'),
        sortable: true,
        formatter: (_col, _key, item): string => {
            return formatTime(item.connected_time)
        },
        selectable: true,
        visible: true
    },
    {
        key: 'connected_time_sec',
        label: $gettext('Time (sec)'),
        sortable: false,
        formatter: (_col, _key, item): string => {
            return String(item.connected_time);
        },
        selectable: true,
        visible: false
    },
    {
        key: 'connected_on',
        label: $gettext('Start Time'),
        sortable: true,
        formatter: (_col, _key, item) => formatTimestampAsDateTime(
            item.connected_on,
            DateTime.DATETIME_SHORT
        ),
        selectable: true,
        visible: false
    },
    {
        key: 'connected_until',
        label: $gettext('End Time'),
        sortable: true,
        formatter: (_col, _key, item) => formatTimestampAsDateTime(
            item.connected_until,
            DateTime.DATETIME_SHORT
        ),
        selectable: true,
        visible: false
    },
    {
        key: 'device.client',
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
        sorter: (row: Row): string => {
            return row.location?.country + ' ' + row.location?.region + ' ' + row.location?.city;
        },
        selectable: true,
        visible: true
    }
];

const exportUrl = computed(() => {
    const exportUrl = new URL(apiUrl.value, document.location.href);
    const exportUrlParams = exportUrl.searchParams;
    exportUrlParams.set('format', 'csv');

    if (!isLive.value) {
        const startDate = DateTime.fromJSDate(dateRange.value.startDate);
        if (startDate.isValid) {
            exportUrlParams.set('start', startDate.toISO());
        }

        const endDate = DateTime.fromJSDate(dateRange.value.endDate);
        if (endDate.isValid) {
            exportUrlParams.set('end', endDate.toISO());
        }
    }

    return exportUrl.toString();
});

const {axios} = useAxios();

const $dataTable = useTemplateRef('$dataTable');
const {navigate} = useHasDatatable($dataTable);

const hasFilters: ComputedRef<boolean> = computed(() => {
    return null !== filters.value.minLength
        || null !== filters.value.maxLength
        || ListenerTypeFilters.All !== filters.value.type;
});

const {data: allListeners, isLoading} = useQuery<Row[]>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationReports,
            'listeners',
            computed(() => (isLive.value) ? 'live' : dateRange.value)
        ],
    ),
    queryFn: async ({signal}) => {
        const params: {
            [key: string]: any
        } = {};

        if (!isLive.value) {
            params.start = DateTime.fromJSDate(dateRange.value.startDate).toISO();
            params.end = DateTime.fromJSDate(dateRange.value.endDate).toISO();
        }

        const {data} = await axios.get<Row[]>(apiUrl.value, {signal, params});
        return data;
    },
    staleTime: 10 * 1000,
    refetchInterval: (query) => {
        const queryKey = query.options?.queryKey ?? [];
        const broadcastType = [...queryKey].pop();
        return (broadcastType === 'live') ? 15000 : false;
    }
});

const filteredListeners = computed<Row[]>(() => {
    const listeners = allListeners.value ?? [];

    if (!hasFilters.value) {
        return listeners;
    }

    return filter(
        listeners,
        (row: Row) => {
            const connectedTime: number = row.connected_time;
            if (null !== filters.value.minLength && connectedTime < filters.value.minLength) {
                return false;
            }
            if (null !== filters.value.maxLength && connectedTime > filters.value.maxLength) {
                return false;
            }
            if (ListenerTypeFilters.All !== filters.value.type) {
                if (ListenerTypeFilters.Mobile === filters.value.type && !row.device.is_mobile) {
                    return false;
                } else if (ListenerTypeFilters.Desktop === filters.value.type && row.device.is_mobile) {
                    return false;
                }
            }

            return true;
        }
    );
});

const queryClient = useQueryClient();

const listenersItemProvider = useClientItemProvider<Row>(
    filteredListeners,
    isLoading,
    undefined,
    async (): Promise<void> => {
        await queryClient.invalidateQueries({
            queryKey: queryKeyWithStation(
                [
                    QueryKeys.StationReports,
                    'listeners'
                ],
            )
        });
    }
);

const totalListenerHours = computed(() => {
    let tlh_seconds = 0;

    const listeners = allListeners.value ?? [];

    for (const listener of listeners) {
        tlh_seconds += listener.connected_time;
    }

    const tlh_hours = tlh_seconds / 3600;
    return Math.round((tlh_hours + 0.00001) * 100) / 100;
});

const setIsLive = (newValue: boolean) => {
    isLive.value = newValue;
    navigate();
};
</script>
