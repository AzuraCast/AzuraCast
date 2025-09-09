<template>
    <card-page
        v-if="processedScheduleItems.length > 0"
        class="scheduled"
        header-id="hdr_scheduled"
        :title="$gettext('Scheduled')"
    >
        <table class="table table-striped mb-0">
            <tbody>
                <tr
                    v-for="row in processedScheduleItems"
                    :key="row.id"
                >
                    <td>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <h5 class="m-0">
                                <small>
                                    <template v-if="row.type === 'playlist'">
                                        {{ $gettext('Playlist') }}
                                    </template>
                                    <template v-else>
                                        {{ $gettext('Streamer/DJ') }}
                                    </template>
                                </small><br>
                                {{ row.name }}
                            </h5>
                            <p class="text-end m-0">
                                <small>{{ row.start_formatted }} - {{ row.end_formatted }}</small>
                                <br>
                                <strong>
                                    <template v-if="row.is_now">
                                        {{ $gettext('Now') }}
                                    </template>
                                    <template v-else>{{ row.time_until }}</template>
                                </strong>
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </card-page>
</template>

<script setup lang="ts">
import {map} from "es-toolkit/compat";
import {computed} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {ApiStationSchedule} from "~/entities/ApiInterfaces.ts";

defineOptions({
    inheritAttrs: false
});

const props = defineProps<{
    scheduleItems: Required<ApiStationSchedule>[]
}>();

const {DateTime} = useLuxon();
const {
    now,
    timestampToDateTime,
    formatDateTime
} = useStationDateTimeFormatter();

type ScheduleWithDetails = Required<ApiStationSchedule> & {
    time_until: string,
    start_formatted: string,
    end_formatted: string
}

const processedScheduleItems = computed<ScheduleWithDetails[]>(() => {
    const nowTz = now();

    return map(props.scheduleItems, (row) => {
        const startMoment = timestampToDateTime(row.start_timestamp);
        const endMoment = timestampToDateTime(row.end_timestamp);

        const newRow: ScheduleWithDetails = {
            ...row,
            time_until: startMoment.toRelative({round: false}) ?? 'N/A',
            start_formatted: formatDateTime(
                startMoment,
                startMoment.hasSame(nowTz, 'day')
                    ? DateTime.TIME_SIMPLE
                    : DateTime.DATETIME_MED
            ),
            end_formatted: formatDateTime(
                endMoment,
                endMoment.hasSame(startMoment, 'day')
                    ? DateTime.TIME_SIMPLE
                    : DateTime.DATETIME_MED
            )
        };

        return newRow;
    });
});
</script>
