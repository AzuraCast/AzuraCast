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
                    <icon-ic-remove/>
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
                    :field="r$.permissions"
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
import {get} from "es-toolkit/compat";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {ApiAdminRoleStationPermission} from "~/entities/ApiInterfaces.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import IconIcRemove from "~icons/ic/baseline-remove";

type T = ApiAdminRoleStationPermission;

const props = defineProps<{
    stations: Record<number, string>,
    stationPermissions: SimpleFormOptionInput,
}>();

defineEmits<{
    (e: 'remove'): void
}>();

const row = defineModel<T>('row', {required: true});

const {r$} = useAppScopedRegle(
    row,
    {
        permissions: {}
    },
    {
        namespace: 'admin-permissions'
    }
);

const getStationName = (stationId: number) => {
    return get(props.stations, stationId, null);
};
</script>
