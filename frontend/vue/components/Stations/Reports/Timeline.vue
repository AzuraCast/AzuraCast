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
                        <icon icon="file_download" />
                        <span>
                            {{ $gettext('Download CSV') }}
                        </span>
                    </a>
                </div>
                <div class="flex-shrink buttons ms-lg-2 mt-2 mt-lg-0">
                    <date-range-dropdown
                        v-model="dateRange"
                        time-picker
                        :tz="timezone"
                    />
                </div>
            </div>
        </div>
        <data-table
            ref="$datatable"
            responsive
            paginated
            select-fields
            :fields="fields"
            :api-url="apiUrl"
        >
            <template #cell(delta)="row">
                <span class="typography-subheading">
                    <template v-if="row.item.delta_total > 0">
                        <span class="text-success">
                            <icon icon="trending_up" />
                            {{ abs(row.item.delta_total) }}
                        </span>
                    </template>
                    <template v-else-if="row.item.delta_total < 0">
                        <span class="text-danger">
                            <icon icon="trending_down" />
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

<script setup>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {computed, ref, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useLuxon} from "~/vendor/luxon";
import {getStationApiUrl} from "~/router";

const baseApiUrl = getStationApiUrl('/history');

const {timezone} = useAzuraCastStation();
const {DateTime} = useLuxon();

const nowTz = DateTime.now().setZone(timezone);

const dateRange = ref(
    {
        startDate: nowTz.minus({days: 13}).toJSDate(),
        endDate: nowTz.toJSDate(),
    }
);

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();

const fields = [
    {
        key: 'played_at',
        label: $gettext('Date/Time (Browser)'),
        selectable: true,
        sortable: false,
        formatter: (value) => {
            return DateTime.fromSeconds(
                value,
                {zone: 'system'}
            ).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...timeConfig}
            );
        }
    },
    {
        key: 'played_at_station',
        label: $gettext('Date/Time (Station)'),
        sortable: false,
        selectable: true,
        visible: false,
        formatter: (value, key, item) => {
            return DateTime.fromSeconds(
                item.played_at,
                {zone: timezone}
            ).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...timeConfig}
            );
        }
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
    const apiUrl = new URL(baseApiUrl.value, document.location);

    const apiUrlParams = apiUrl.searchParams;
    apiUrlParams.set('start', DateTime.fromJSDate(dateRange.value.startDate).toISO());
    apiUrlParams.set('end', DateTime.fromJSDate(dateRange.value.endDate).toISO());

    return apiUrl.toString();
});

const exportUrl = computed(() => {
    const exportUrl = new URL(apiUrl.value, document.location);
    const exportUrlParams = exportUrl.searchParams;

    exportUrlParams.set('format', 'csv');

    return exportUrl.toString();
});

const abs = (val) => {
    return Math.abs(val);
};

const $datatable = ref(); // Template Ref

const relist = () => {
    $datatable.value.relist();
};

watch(dateRange, relist);
</script>
