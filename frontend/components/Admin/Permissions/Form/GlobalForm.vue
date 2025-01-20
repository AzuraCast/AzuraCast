<template>
    <tab :label="$gettext('Global Permissions')">
        <div class="row g-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Role Name')"
            />

            <form-group-multi-check
                id="edit_form_global_permissions"
                class="col-md-12"
                :field="v$.permissions.global"
                :options="globalPermissions"
                stacked
                :label="$gettext('Global Permissions')"
                :description="$gettext('Users with this role will have these permissions across the entire installation.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab.ts";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {Permission} from "~/components/Admin/Permissions/EditModal.vue";
import {required} from "@vuelidate/validators";

type T = Permission;

interface PermissionGlobalFormProps extends FormTabProps<T> {
    globalPermissions: SimpleFormOptionInput,
}

const props = defineProps<PermissionGlobalFormProps>();

const emit = defineEmits<FormTabEmits<T>>();

const {v$} = useVuelidateOnFormTab(
    props,
    emit,
    {
        'name': {required},
        'permissions': {
            'global': {},
        }
    },
    () => ({
        'name': '',
        'permissions': {
            'global': [],
        }
    })
);
</script>
