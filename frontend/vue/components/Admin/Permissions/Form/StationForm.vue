<template>
    <b-tab :title="langTabTitle">
        <b-card v-for="(row, index) in form.permissions.station.$each.$iter" :key="index" class="mb-3" no-body>
            <div class="card-header bg-primary-dark d-flex align-items-center">
                <div class="flex-fill">
                    <h2 class="card-title">
                        {{ getStationName(row.station_id.$model) }}
                    </h2>
                </div>
                <div class="flex-shrink-0">
                    <b-button size="sm" variant="outline-light" class="py-2 pr-0" @click.prevent="remove(index)">
                        <icon icon="remove"></icon>
                        <translate key="lang_btn_remove">Remove</translate>
                    </b-button>
                </div>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12"
                                              :id="'edit_form_station_permissions_'+row.station_id.$model"
                                              :field="row.permissions">
                            <template #label="{lang}">
                                <translate :key="lang">Station Permissions</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Users with this role will have these permissions for this single station.</translate>
                            </template>
                            <template #default="props">
                                <b-form-checkbox-group :id="props.id" :options="stationPermissionOptions"
                                                       v-model="props.field.$model">
                                </b-form-checkbox-group>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>

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

export default {
    name: 'AdminPermissionsStationForm',
    components: {BWrappedFormGroup, Icon},
    props: {
        form: Object,
        stations: Object,
        stationPermissions: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Station Permissions');
        },
        stationPermissionOptions() {
            return _.map(this.stationPermissions, (permissionName, permissionKey) => {
                return {
                    text: permissionName,
                    value: permissionKey
                };
            })
        },
        remainingStations() {
            return _.pickBy(this.stations, (stationName, stationId) => {
                return !_.find(this.form.permissions.station.$model, {'station_id': stationId});
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
            this.form.permissions.station.$model.splice(index, 1);
        },
        add(stationId) {
            this.form.permissions.station.$model.push({
                'station_id': stationId,
                'permissions': []
            });
        }
    }
};
</script>
