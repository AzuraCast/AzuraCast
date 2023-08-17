<template>
    <section
        class="card mb-3"
        role="region"
    >
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ getStationName(row.station_id) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <button
                    type="button"
                    class="btn btn-sm btn-light py-2"
                    @click="$emit('remove')"
                >
                    <icon icon="remove" />
                    <span>
                        {{ $gettext('Remove') }}
                    </span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <form-group-multi-check
                    :id="'edit_form_station_permissions_'+row.station_id"
                    class="col-md-12"
                    :field="v$.permissions"
                    :options="stationPermissionOptions"
                    stacked
                    :label="$gettext('Station Permissions')"
                    :description="$gettext('Users with this role will have these permissions for this single station.')"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import useVuelidate from "@vuelidate/core";
import {get, map} from "lodash";
import Icon from "~/components/Common/Icon.vue";
import {useVModel} from "@vueuse/core";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

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
