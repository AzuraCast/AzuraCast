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
                :form="v$"
                :global-permissions="globalPermissions"
            />

            <admin-permissions-station-form
                :form="v$"
                :stations="stations"
                :station-permissions="stationPermissions"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, ref} from "vue";
import {baseEditModalProps, ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal";
import {useTranslate} from "~/vendor/gettext";
import {required} from '@vuelidate/validators';
import AdminPermissionsGlobalForm from "./Form/GlobalForm.vue";
import AdminPermissionsStationForm from "./Form/StationForm.vue";
import {forEach, map} from 'lodash';
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...baseEditModalProps,
    stations: {
        type: Object,
        required: true
    },
    globalPermissions: {
        type: Object,
        required: true
    },
    stationPermissions: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['relist']);

const $modal = ref<ModalFormTemplateRef>(null);

const {
    loading,
    error,
    isEditMode,
    v$,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    {
        'name': {required},
        'permissions': {
            'global': {},
            'station': {},
        }
    },
    {
        'name': '',
        'permissions': {
            'global': [],
            'station': [],
        }
    },
    {
        populateForm(data, formRef) {
            formRef.value = {
                name: data.name,
                permissions: {
                    global: data.permissions.global,
                    station: map(data.permissions.station, (permissions, stationId) => {
                        return {
                            'station_id': stationId,
                            'permissions': permissions
                        };
                    })
                }
            };
        },
        getSubmittableFormData(formRef) {
            const formValue = formRef.value;

            const formReturn = {
                name: formValue.name,
                permissions: {
                    global: formValue.permissions.global,
                    station: {}
                }
            };

            forEach(formValue.permissions.station, (row) => {
                formReturn.permissions.station[row.station_id] = row.permissions;
            });

            return formReturn;
        },
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
