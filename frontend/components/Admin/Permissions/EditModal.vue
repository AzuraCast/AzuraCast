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

<script lang="ts">
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
</script>

<script setup lang="ts">
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, ref} from "vue";
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    ModalFormTemplateRef,
    useBaseEditModal
} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import AdminPermissionsGlobalForm from "./Form/GlobalForm.vue";
import AdminPermissionsStationForm from "./Form/StationForm.vue";
import Tabs from "~/components/Common/Tabs.vue";

interface PermissionsEditModalProps extends BaseEditModalProps {
    stations: Record<string, string>,
    globalPermissions: Record<string, string>,
    stationPermissions: Record<string, string>,
}

const props = defineProps<PermissionsEditModalProps>();
const emit = defineEmits<BaseEditModalEmits>();

const $modal = ref<ModalFormTemplateRef>(null);

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
