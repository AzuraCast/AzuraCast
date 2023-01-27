<template>
    <div id="profile-scheduled">
        <section
            v-if="processedScheduleItems.length > 0"
            class="card mb-4 scheduled"
            role="region"
        >
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Scheduled') }}
                </h3>
            </div>
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
                                <p class="text-right m-0">
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
        </section>
    </div>
</template>

<script setup>
import {DateTime} from "luxon";
import {map} from "lodash";
import {computed} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    scheduleItems: {
        type: Array,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    }
});

const {timeConfig} = useAzuraCast();

const processedScheduleItems = computed(() => {
    const now = DateTime.now().setZone(props.stationTimeZone);

    return map(props.scheduleItems, (row) => {
        const start_moment = DateTime.fromSeconds(row.start_timestamp).setZone(props.stationTimeZone);
        const end_moment = DateTime.fromSeconds(row.end_timestamp).setZone(props.stationTimeZone);

        row.time_until = start_moment.toRelative();

        if (start_moment.hasSame(now, 'day')) {
            row.start_formatted = start_moment.toLocaleString(
                {...DateTime.TIME_SIMPLE, ...timeConfig}
            );
        } else {
            row.start_formatted = start_moment.toLocaleString(
                {...DateTime.DATETIME_MED, ...timeConfig}
            );
        }

        if (end_moment.hasSame(start_moment, 'day')) {
            row.end_formatted = end_moment.toLocaleString(
                {...DateTime.TIME_SIMPLE, ...timeConfig}
            );
        } else {
            row.end_formatted = end_moment.toLocaleString(
                {...DateTime.DATETIME_MED, ...timeConfig}
            );
        }

        return row;
    });
});
</script>
