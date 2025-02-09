<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
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
import {GlobalPermission, StationPermission} from "~/acl.ts";

export interface PermissionStation {
    id: number,
    permissions: string[]
}

export interface Permission {
    name: string,
    permissions: {
        global: string[],
        station: PermissionStation[],
    }
}

interface PermissionsEditModalProps extends BaseEditModalProps {
    stations: Record<number, string>,
    globalPermissions: Record<GlobalPermission, string>,
    stationPermissions: Record<StationPermission, string>,
}

const props = defineProps<PermissionsEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

const {
    loading,
    error,
    isEditMode,
    v$,
    form,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<Permission>(
    props,
    emit,
    $modal
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
