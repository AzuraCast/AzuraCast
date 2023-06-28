<template>
    <b-tab :title="$gettext('Station Permissions')">
        <permissions-form-station-row
            v-for="(row, index) in form.permissions.$model.station"
            :key="index"
            v-model:row="form.permissions.$model.station[index]"
            :stations="stations"
            :station-permissions="stationPermissions"
            @remove="remove(index)"
        />

        <div
            v-if="hasRemainingStations"
            class="btn-group btn-group-sm"
        >
            <b-dropdown
                size="sm"
                variant="outline-primary"
            >
                <template #button-content>
                    {{ $gettext('Add Station') }}
                </template>
                <div style="max-height: 300px; overflow-y: auto;">
                    <b-dropdown-item-button
                        v-for="(stationName, stationId) in remainingStations"
                        :key="stationId"
                        @click="add(stationId)"
                    >
                        {{ stationName }}
                    </b-dropdown-item-button>
                </div>
            </b-dropdown>
        </div>
    </b-tab>
</template>

<script setup>
import {find, isEmpty, pickBy} from 'lodash';
import PermissionsFormStationRow from "~/components/Admin/Permissions/Form/StationRow.vue";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    stations: {
        type: Object,
        required: true
    },
    stationPermissions: {
        type: Object,
        required: true
    }
});


const remainingStations = computed(() => {
    return pickBy(props.stations, (stationName, stationId) => {
        return !find(props.form.permissions.$model.station, {'station_id': stationId});
    });
});

const hasRemainingStations = computed(() => {
    return !isEmpty(remainingStations.value);
});


const remove = (index) => {
    props.form.permissions.$model.station.splice(index, 1);
};

const add = (stationId) => {
    props.form.permissions.$model.station.push({
        'station_id': stationId,
        'permissions': []
    });
};
</script>
