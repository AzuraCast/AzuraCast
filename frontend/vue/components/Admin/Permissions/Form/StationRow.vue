<template>
    <b-card
        class="mb-3"
        no-body
    >
        <div class="card-header bg-primary-dark d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ getStationName(row.station_id) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <b-button
                    size="sm"
                    variant="outline-light"
                    class="py-2 pr-0"
                    @click.prevent="$emit('remove')"
                >
                    <icon icon="remove" />
                    {{ $gettext('Remove') }}
                </b-button>
            </div>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        :id="'edit_form_station_permissions_'+row.station_id"
                        class="col-md-12"
                        :field="v$.permissions"
                    >
                        <template #label>
                            {{ $gettext('Station Permissions') }}
                        </template>
                        <template #description>
                            {{ $gettext('Users with this role will have these permissions for this single station.') }}
                        </template>
                        <template #default="slotProps">
                            <b-form-checkbox-group
                                :id="slotProps.id"
                                v-model="slotProps.field.$model"
                                :options="stationPermissionOptions"
                                stacked
                            />
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </b-card>
</template>

<script setup>
import useVuelidate from "@vuelidate/core";
import {get, map} from "lodash";
import Icon from "~/components/Common/Icon.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {useVModel} from "@vueuse/core";
import {computed} from "vue";

const props = defineProps({
    row: {
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

const emit = defineEmits(['remove', 'update:row']);

const form = useVModel(props, 'row', emit);

const validations = {
    'station_id': {},
    'permissions': {},
};

const v$ = useVuelidate(validations, form);

const stationPermissionOptions = computed(() => {
    return map(props.stationPermissions, (permissionName, permissionKey) => {
        return {
            text: permissionName,
            value: permissionKey
        };
    })
});

const getStationName = (stationId) => {
    return get(props.stations, stationId, null);
};
</script>
