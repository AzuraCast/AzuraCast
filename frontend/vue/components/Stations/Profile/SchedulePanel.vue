<template>
    <div id="profile-scheduled">
        <section class="card mb-4 scheduled" role="region" v-if="processedScheduleItems.length > 0">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title" key="lang_schedule_title" v-translate>Scheduled</h3>
            </div>
            <table class="table table-striped mb-0">
                <tbody>
                <tr v-for="row in processedScheduleItems">
                    <td>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <h5 class="m-0">
                                <small>
                                    <template v-if="row.type === 'playlist'">
                                        <translate key="lang_schedule_playlist_name">Playlist</translate>
                                    </template>
                                    <template v-else>
                                        <translate key="lang_schedule_streamer_name">Streamer/DJ</translate>
                                    </template>
                                </small><br>
                                {{ row.name }}
                            </h5>
                            <p class="text-right m-0">
                                <small>{{ row.start_formatted }} - {{ row.end_formatted }}</small>
                                <br>
                                <strong>
                                    <template v-if="row.is_now">
                                        <translate key="lang_schedule_now">Now</translate>
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

<script>
import {DateTime} from "luxon";
import _ from "lodash";

export default {
    inheritAttrs: false,
    props: {
        scheduleItems: Array,
        stationTimeZone: String
    },
    computed: {
        processedScheduleItems() {
            const now = DateTime.now().setZone(this.stationTimeZone);

            return _.map(this.scheduleItems, (row) => {
                const start_moment = DateTime.fromSeconds(row.start_timestamp).setZone(this.stationTimeZone);
                const end_moment = DateTime.fromSeconds(row.end_timestamp).setZone(this.stationTimeZone);

                row.time_until = start_moment.toRelative();

                if (start_moment.hasSame(now, 'day')) {
                    row.start_formatted = start_moment.toLocaleString(
                        {...DateTime.TIME_SIMPLE, ...App.time_config}
                    );
                } else {
                    row.start_formatted = start_moment.toLocaleString(
                        {...DateTime.DATETIME_MED, ...App.time_config}
                    );
                }

                if (end_moment.hasSame(start_moment, 'day')) {
                    row.end_formatted = end_moment.toLocaleString(
                        {...DateTime.TIME_SIMPLE, ...App.time_config}
                    );
                } else {
                    row.end_formatted = end_moment.toLocaleString(
                        {...DateTime.DATETIME_MED, ...App.time_config}
                    );
                }

                return row;
            });
        }
    }
};
</script>
