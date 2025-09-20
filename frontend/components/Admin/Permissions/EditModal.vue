<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs content-class="mt-3">
            <admin-permissions-global-form
                v-model:form="form"
                :global-permissions="globalPermissions"
            />

            <admin-permissions-station-form
                v-model:form="form"
                :stations="stations"
                :station-permissions="stationPermissions"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, toRef, useTemplateRef} from "vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import AdminPermissionsGlobalForm from "~/components/Admin/Permissions/Form/GlobalForm.vue";
import AdminPermissionsStationForm from "~/components/Admin/Permissions/Form/StationForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {ApiAdminRoleStationPermission, GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppCollectScope} from "~/vendor/regle.ts";
import mergeExisting from "~/functions/mergeExisting.ts";

const props = defineProps<BaseEditModalProps & {
    stations: Record<number, string>,
    globalPermissions: Record<GlobalPermissions, string>,
    stationPermissions: Record<StationPermissions, string>,
}>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

export type PermissionsRecord = {
    name: string;
    permissions: {
        global: string[],
        station: ApiAdminRoleStationPermission[]
    }
}

const {record: form, reset: resetFormRef} = useResettableRef<PermissionsRecord>({
    name: '',
    permissions: {
        global: [],
        station: [],
    }
});

const {r$} = useAppCollectScope('admin-permissions');

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<PermissionsRecord>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    () => {
        resetFormRef();
        r$.$reset();
    },
    (data) => {
        form.value = mergeExisting(form.value, data);
        r$.$reset();
    },
    async () => {
        const {valid} = await r$.$validate();
        return {valid, data: form.value};
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Role')
        : $gettext('Add Role');
});

defineExpose({
    create,
    edit,
    close
});
</script>
