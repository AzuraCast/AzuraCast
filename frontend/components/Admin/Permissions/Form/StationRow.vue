<template>
    <section
        class="card mb-3"
        role="region"
    >
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ getStationName(row.id) }}
                </h2>
            </div>
            <div class="flex-shrink-0">
                <button
                    type="button"
                    class="btn btn-sm btn-light py-2"
                    @click="$emit('remove')"
                >
                    <icon :icon="IconRemove" />
                    <span>
                        {{ $gettext('Remove') }}
                    </span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <form-group-multi-check
                    :id="'edit_form_station_permissions_'+row.id"
                    class="col-md-12"
                    :field="v$.permissions"
                    :options="stationPermissions"
                    stacked
                    :label="$gettext('Station Permissions')"
                    :description="$gettext('Users with this role will have these permissions for this single station.')"
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import {get} from "lodash";
import Icon from "~/components/Common/Icon.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {IconRemove} from "~/components/Common/icons";
import {PermissionStation} from "~/components/Admin/Permissions/EditModal.vue";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import useVuelidate from "@vuelidate/core";
import {useVModel} from "@vueuse/core";

type T = PermissionStation;

const props = defineProps<{
    row: T,
    stations: Record<number, string>,
    stationPermissions: SimpleFormOptionInput,
}>();

const emit = defineEmits<{
    (e: 'update:row', row: T)
    (e: 'remove'): void
}>();

const row = useVModel(props, 'row', emit);

const v$ = useVuelidate<T>(
    {
        permissions: {}
    },
    row
);

const getStationName = (stationId: number) => {
    return get(props.stations, stationId, null);
};
</script>
