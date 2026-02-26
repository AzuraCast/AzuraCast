<template>
    <div class="card">
        <div class="card-header text-bg-primary">
            <div class="d-lg-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    {{ $gettext('Song Playback Timeline') }}
                </h2>
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
                <div class="flex-shrink buttons ms-lg-2 mt-2 mt-lg-0">
                    <date-range-dropdown
                        v-model="dateRange"
                        :options="{
                            timeConfig: {
                                enableTimePicker: true,
                            },
                            timezone: timezone
                        }"
                        class="btn-dark"
                    />
                </div>
            </div>
        </div>
        <data-table
            ref="$dataTable"
            paginated
            select-fields
            :fields="fields"
            :provider="listItemProvider"
        >
            <template #cell(delta)="row">
                <span class="typography-subheading">
                    <template v-if="row.item.delta_total > 0">
                        <span class="text-success">
                            <icon-ic-trending-up/>
                            {{ abs(row.item.delta_total) }}
                        </span>
                    </template>
                    <template v-else-if="row.item.delta_total < 0">
                        <span class="text-danger">
                            <icon-ic-trending-down/>
                            {{ abs(row.item.delta_total) }}
                        </span>
                    </template>
                    <template v-else>
                        0
                    </template>
                </span>
            </template>
            <template #cell(song)="row">
                <div :class="{'text-muted': !row.item.is_visible}">
                    <template v-if="row.item.song.title">
                        <b>{{ row.item.song.title }}</b><br>
                        {{ row.item.song.artist }}
                    </template>
                    <template v-else>
                        {{ row.item.song.text }}
                    </template>
                </div>
            </template>
            <template #cell(source)="row">
                <template v-if="row.item.is_request">
                    {{ $gettext('Listener Request') }}
                </template>
                <template v-else-if="row.item.playlist">
                    {{ $gettext('Playlist:') }}
                    {{ row.item.playlist }}
                </template>
                <template v-else-if="row.item.streamer">
                    {{ $gettext('Live Streamer:') }}
                    {{ row.item.streamer }}
                </template>
                <template v-else>
                    &nbsp;
                </template>
            </template>
        </data-table>
    </div>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown.vue";
import {computed, nextTick, ref, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useHasDatatable from "~/functions/useHasDatatable.ts";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";
import IconIcCloudDownload from "~icons/ic/baseline-cloud-download";
import IconIcTrendingDown from "~icons/ic/baseline-trending-down";
import IconIcTrendingUp from "~icons/ic/baseline-trending-up";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const baseApiUrl = getStationApiUrl('/history');

const stationData = useStationData();
const {timezone} = toRefs(stationData);

const {DateTime} = useLuxon();
const {
    now,
    formatDateTimeAsDateTime,
    formatTimestampAsDateTime
} = useStationDateTimeFormatter();

const nowTz = now();

const dateRange = ref(
    {
        startDate: nowTz.minus({days: 13}).toJSDate(),
        endDate: nowTz.toJSDate(),
    }
);

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {
        key: 'played_at',
        label: $gettext('Date/Time (Browser)'),
        selectable: true,
        sortable: false,
        visible: false,
        formatter: (value) => formatDateTimeAsDateTime(
            DateTime.fromSeconds(value, {zone: 'system'}),
            DateTime.DATETIME_SHORT
        )
    },
    {
        key: 'played_at_station',
        label: $gettext('Date/Time (Station)'),
        sortable: false,
        selectable: true,
        visible: true,
        formatter: (_value, _key, item) => formatTimestampAsDateTime(
            item.played_at,
            DateTime.DATETIME_SHORT
        )
    },
    {
        key: 'listeners_start',
        label: $gettext('Listeners'),
        selectable: true,
        sortable: false
    },
    {
        key: 'delta',
        label: $gettext('Change'),
        selectable: true,
        sortable: false
    },
    {
        key: 'song',
        isRowHeader: true,
        label: $gettext('Song Title'),
        selectable: true,
        sortable: false
    },
    {
        key: 'source',
        label: $gettext('Source'),
        selectable: true,
        sortable: false
    }
];

const apiUrl = computed(() => {
    const apiUrl = new URL(baseApiUrl.value, document.location.href);

    const apiUrlParams = apiUrl.searchParams;

    const startDate = DateTime.fromJSDate(dateRange.value.startDate);
    if (startDate.isValid) {
        apiUrlParams.set('start', startDate.toISO());
    }

    const endDate = DateTime.fromJSDate(dateRange.value.endDate);
    if (endDate.isValid) {
        apiUrlParams.set('end', endDate.toISO());
    }

    return apiUrl.toString();
});

const exportUrl = computed(() => {
    const exportUrl = new URL(apiUrl.value, document.location.href);
    const exportUrlParams = exportUrl.searchParams;

    exportUrlParams.set('format', 'csv');

    return exportUrl.toString();
});

const listItemProvider = useApiItemProvider(
    apiUrl,
    queryKeyWithStation([
        QueryKeys.StationReports,
        'timeline',
        dateRange
    ])
);

const abs = (val: number) => {
    return Math.abs(val);
};

const $dataTable = useTemplateRef('$dataTable');
const {navigate} = useHasDatatable($dataTable);

watch(dateRange, () => void nextTick(navigate));
</script>
