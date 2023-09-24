<template>
    <tab :label="$gettext('Station Permissions')">
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
            <div class="dropdown btn-group">
                <button
                    class="btn btn-sm btn-primary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    {{ $gettext('Add Station') }}
                    <span class="caret" />
                </button>
                <ul class="dropdown-menu">
                    <li
                        v-for="(stationName, stationId) in remainingStations"
                        :key="stationId"
                    >
                        <button
                            type="button"
                            class="dropdown-item"
                            @click="add(stationId)"
                        >
                            {{ stationName }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </tab>
</template>

<script setup lang="ts">
import {find, isEmpty, pickBy} from 'lodash';
import PermissionsFormStationRow from "~/components/Admin/Permissions/Form/StationRow.vue";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";

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
    return pickBy(props.stations, (_stationName, stationId) => {
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
