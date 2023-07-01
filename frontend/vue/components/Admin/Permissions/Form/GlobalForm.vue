<template>
    <o-tab-item
        :label="$gettext('Global Permissions')"
        active
    >
        <b-form-group>
            <div class="row g-3">
                <form-group-field
                    id="edit_form_name"
                    class="col-md-12"
                    :field="form.name"
                >
                    <template #label>
                        {{ $gettext('Role Name') }}
                    </template>
                </form-group-field>

                <form-group-field
                    id="edit_form_global_permissions"
                    class="col-md-12"
                    :field="form.permissions.global"
                >
                    <template #label>
                        {{ $gettext('Global Permissions') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Users with this role will have these permissions across the entire installation.')
                        }}
                    </template>
                    <template #default="slotProps">
                        <b-form-checkbox-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :options="globalPermissionOptions"
                            stacked
                        />
                    </template>
                </form-group-field>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {map} from 'lodash';
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    globalPermissions: {
        type: Object,
        required: true
    }
});

const globalPermissionOptions = computed(() => {
    return map(props.globalPermissions, (permissionName, permissionKey) => {
        return {
            text: permissionName,
            value: permissionKey
        };
    });
});
</script>
