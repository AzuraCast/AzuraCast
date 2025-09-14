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
import {computed, useTemplateRef} from "vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import AdminPermissionsGlobalForm from "~/components/Admin/Permissions/Form/GlobalForm.vue";
import AdminPermissionsStationForm from "~/components/Admin/Permissions/Form/StationForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {ApiAdminRoleStationPermission, GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppCollectScope} from "~/vendor/regle.ts";

interface PermissionsEditModalProps extends BaseEditModalProps {
    stations: Record<number, string>,
    globalPermissions: Record<GlobalPermissions, string>,
    stationPermissions: Record<StationPermissions, string>,
}

const props = defineProps<PermissionsEditModalProps>();
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
} = useBaseEditModal(
    form,
    props,
    emit,
    $modal,
    () => {
        resetFormRef();
        r$.$reset();
    },
    async () => (await r$.$validate()).valid,
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
