<template>
    <tab :label="$gettext('Station Permissions')">
        <permissions-form-station-row
            v-for="(row, index) in form.permissions?.station ?? []"
            :key="index"
            v-model:row="form.permissions.station[index]"
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
import {computed, toRaw} from "vue";
import Tab from "~/components/Common/Tab.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab.ts";
import {Permission} from "~/components/Admin/Permissions/EditModal.vue";

type T = Permission;

interface PermissionStationFormProps extends FormTabProps<T> {
    stations: Record<string, string>,
    stationPermissions: Record<string, string>,
}

const props = defineProps<PermissionStationFormProps>();

const emit = defineEmits<FormTabEmits<T>>();

const {form} = useVuelidateOnFormTab(
    props,
    emit,
    {
        permissions: {
            station: {}
        }
    },
    () => ({
        permissions: {
            station: []
        }
    })
);

const remainingStations = computed(() => {
    const usedStations = form.value.permissions?.station ?? [];

    return pickBy(toRaw(props.stations), (_stationName, stationId) => {
        return !find(usedStations, (station) => station.id === Number(stationId));
    });
});

const hasRemainingStations = computed(() => {
    return !isEmpty(remainingStations.value);
});

const remove = (index: number) => {
    form.value.permissions.station.splice(index, 1);
};

const add = (stationId: string | number) => {
    form.value.permissions.station.push({
        'id': Number(stationId),
        'permissions': []
    });
};
</script>
