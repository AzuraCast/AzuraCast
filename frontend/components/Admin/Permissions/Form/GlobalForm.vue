<template>
    <tab :label="$gettext('Global Permissions')">
        <div class="row g-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('Role Name')"
            />

            <form-group-multi-check
                id="edit_form_global_permissions"
                class="col-md-12"
                :field="r$.permissions.global"
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
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {required} from "@regle/rules";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {PermissionsRecord} from "~/components/Admin/Permissions/EditModal.vue";

defineProps<{
    globalPermissions: SimpleFormOptionInput,
}>();

const form = defineModel<PermissionsRecord>('form', {required: true});

const {r$} = useAppScopedRegle(
    form,
    {
        name: {required},
        permissions: {
            global: {
                $each: {}
            }
        }
    },
    {
        namespace: 'admin-permissions'
    }
);
</script>
