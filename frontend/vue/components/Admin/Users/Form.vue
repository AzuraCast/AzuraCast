<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="edit_form_email" :field="form.email" input-type="email">
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_new_password" :field="form.new_password"
                                  input-type="password">
                <template #label>
                    {{ $gettext('Reset Password') }}
                    {{ $gettext('Password') }}
                </template>
                <template v-if="isEditMode" #description="{lang}">
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                <template #label>
                    {{ $gettext('Display Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_roles"
                                  :field="form.roles">
                <template #label>
                    {{ $gettext('Roles') }}
                </template>
                <template #default="props">
                    <b-form-checkbox-group :id="props.id" :options="roleOptions" v-model="props.field.$model">
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";

const props = defineProps({
    form: Object,
    roles: Object,
    isEditMode: Boolean
});

const roleOptions = computed(() => {
    return objectToFormOptions(props.roles);
});
</script>
