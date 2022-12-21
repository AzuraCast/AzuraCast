<template>
    <b-card class="mb-3" no-body>
        <div class="card-header bg-primary-dark d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ getStationName(row.station_id) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <b-button size="sm" variant="outline-light" class="py-2 pr-0" @click.prevent="$emit('remove')">
                    <icon icon="remove"></icon>
                    {{ $gettext('Remove') }}
                </b-button>
            </div>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group class="col-md-12"
                                          :id="'edit_form_station_permissions_'+row.station_id"
                                          :field="v$.row.permissions">
                        <template #label>
                            {{ $gettext('Station Permissions') }}
                        </template>
                        <template #description>
                            {{ $gettext('Users with this role will have these permissions for this single station.') }}
                        </template>
                        <template #default="props">
                            <b-form-checkbox-group :id="props.id" :options="stationPermissionOptions"
                                                   v-model="props.field.$model">
                            </b-form-checkbox-group>
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import _ from "lodash";
import Icon from "~/components/Common/Icon.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";

export default {
    name: 'PermissionsFormStationRow',
    components: {BWrappedFormGroup, Icon},
    emits: ['remove'],
    props: {
        row: Object,
        stations: Object,
        stationPermissions: Object
    },
    setup() {
        return {
            v$: useVuelidate()
        }
    },
    validations: {
        row: {
            'station_id': {},
            'permissions': {},
        }
    },
    computed: {
        stationPermissionOptions() {
            return _.map(this.stationPermissions, (permissionName, permissionKey) => {
                return {
                    text: permissionName,
                    value: permissionKey
                };
            })
        },
    },
    methods: {
        getStationName(stationId) {
            return _.get(this.stations, stationId, null);
        },
    }
}
</script>
