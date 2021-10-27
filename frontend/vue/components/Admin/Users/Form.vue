<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_email" :field="form.email" input-type="email">
                <template #label="{lang}">
                    <translate :key="lang">E-mail Address</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_new_password" :field="form.new_password"
                                  input-type="password">
                <template #label="{lang}">
                    <translate v-if="isEditMode" :key="lang+'a'">Reset Password</translate>
                    <translate v-else :key="lang+'b'">Password</translate>
                </template>
                <template v-if="isEditMode" #description="{lang}">
                    <translate :key="lang">Leave blank to use the current password.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                <template #label="{lang}">
                    <translate :key="lang">Display Name</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_roles"
                                  :field="form.roles">
                <template #label="{lang}">
                    <translate :key="lang">Roles</translate>
                </template>
                <template #default="props">
                    <b-form-checkbox-group :id="props.id" :options="roleOptions" v-model="props.field.$model">
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AdminUserForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        roles: Object,
        isEditMode: Boolean
    },
    computed: {
        roleOptions() {
            return objectToFormOptions(this.roles);
        }
    }
};
</script>
