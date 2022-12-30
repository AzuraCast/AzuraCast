<template>
    <b-tab :title="$gettext('Schedule')">
        <b-form-group v-if="scheduleItems.length === 0">
            <label>
                {{ $gettext('Not Scheduled') }}
            </label>
            <p>
                {{
                    $gettext('This playlist currently has no scheduled times. It will play at all times. To add a new scheduled time, click the button below.')
                }}
            </p>
        </b-form-group>

        <playlists-form-schedule-row
            v-for="(row, index) in scheduleItems"
            :key="index"
            v-model:row="scheduleItems[index]"
            :station-time-zone="stationTimeZone"
            :index="index"
            @remove="remove(index)"
        />

        <b-button-group>
            <b-button
                size="sm"
                variant="outline-primary"
                @click.prevent="add"
            >
                <icon icon="add" />
                {{ $gettext('Add Schedule Item') }}
            </b-button>
        </b-button-group>
    </b-tab>
</template>

<script>
import Icon from '~/components/Common/Icon';
import PlaylistsFormScheduleRow from "~/components/Stations/Playlists/Form/ScheduleRow.vue";

export default {
    name: 'PlaylistEditSchedule',
    components: {PlaylistsFormScheduleRow, Icon},
    props: {
        form: {
            type: Object,
            required: true
        },
        stationTimeZone: {
            type: String,
            required: true
        },
        scheduleItems: {
            type: Array,
            default: () => {
                return [];
            }
        }
    },
    methods: {
        add() {
            this.scheduleItems.push({
                start_time: null,
                end_time: null,
                start_date: null,
                end_date: null,
                days: [],
                loop_once: false
            });
        },
        remove (index) {
            this.scheduleItems.splice(index, 1);
        }
    }
};
</script>
