<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    {{ $gettext('Song Playback Timeline') }}
                </h2>
                <div class="flex-shrink buttons">
                    <a
                        id="btn-export"
                        class="btn btn-bg"
                        :href="exportUrl"
                        target="_blank"
                    >
                        <icon icon="file_download" />
                        {{ $gettext('Download CSV') }}
                    </a>

                    <date-range-dropdown
                        v-model="dateRange"
                        time-picker
                        :tz="stationTimeZone"
                        @update="relist"
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
import {DateTime} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    baseApiUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});

const nowTz = DateTime.now().setZone(props.stationTimeZone);

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
                {zone: props.stationTimeZone}
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
    let apiUrl = new URL(props.baseApiUrl, document.location);

    let apiUrlParams = apiUrl.searchParams;
    apiUrlParams.set('start', DateTime.fromJSDate(dateRange.value.startDate).toISO());
    apiUrlParams.set('end', DateTime.fromJSDate(dateRange.value.endDate).toISO());

    return apiUrl.toString();
});

const exportUrl = computed(() => {
    let exportUrl = new URL(apiUrl.value, document.location);
    let exportUrlParams = exportUrl.searchParams;

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
</script>
