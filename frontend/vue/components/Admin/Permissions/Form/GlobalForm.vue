<template>
    <b-tab :title="$gettext('Global Permissions')" active>
        <b-form-group>
            <div class="form-row">
                <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                    <template #label>
                        {{ $gettext('Role Name') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_global_permissions"
                                      :field="form.permissions.global">
                    <template #label>
                        {{ $gettext('Global Permissions') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Users with this role will have these permissions across the entire installation.')
                        }}
                    </template>
                    <template #default="props">
                        <b-form-checkbox-group :id="props.id" :options="globalPermissionOptions"
                                               v-model="props.field.$model">
                        </b-form-checkbox-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {map} from 'lodash';
import {computed} from "vue";

const props = defineProps({
  form: Object,
  globalPermissions: Object
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
