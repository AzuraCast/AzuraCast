<template>
    <b-tab :title="langTabTitle">
        <permissions-form-station-row
            v-for="(row, index) in form.permissions.$model.station" :key="index"
            :stations="stations" :station-permissions="stationPermissions"
            :row.sync="row" @remove="remove(index)"
        ></permissions-form-station-row>

        <b-button-group v-if="hasRemainingStations">
            <b-dropdown size="sm" variant="outline-primary">
                <template #button-content>
                    <translate key="lang_btn_add_station">Add Station</translate>
                </template>
                <div style="max-height: 300px; overflow-y: auto;">
                    <b-dropdown-item-button v-for="(stationName, stationId) in remainingStations" :key="stationId"
                                            @click="add(stationId)">{{ stationName }}
                    </b-dropdown-item-button>
                </div>
            </b-dropdown>
        </b-button-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import Icon from "~/components/Common/Icon";
import _ from 'lodash';
import PermissionsFormStationRow from "~/components/Admin/Permissions/Form/StationRow.vue";

export default {
    name: 'AdminPermissionsStationForm',
    components: {PermissionsFormStationRow, BWrappedFormGroup, Icon},
    props: {
        form: Object,
        stations: Object,
        stationPermissions: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Station Permissions');
        },
        remainingStations() {
            return _.pickBy(this.stations, (stationName, stationId) => {
                return !_.find(this.form.permissions.$model.station, {'station_id': stationId});
            });
        },
        hasRemainingStations() {
            return !_.isEmpty(this.remainingStations);
        },
    },
    methods: {
        getStationName(stationId) {
            return _.get(this.stations, stationId, null);
        },
        remove (index) {
            this.form.permissions.$model.station.splice(index, 1);
        },
        add(stationId) {
            this.form.permissions.$model.station.push({
                'station_id': stationId,
                'permissions': []
            });
        }
    }
};
</script>
