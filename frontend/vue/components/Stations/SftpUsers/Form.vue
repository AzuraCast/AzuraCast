<template>
    <b-form-group>
        <b-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_username" :field="form.username">
                <template #label>
                    <translate key="lang_edit_form_username">Username</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_password" :field="form.password"
                                  input-type="password">
                <template #label v-if="isEditMode">
                    <translate key="lang_edit_form_new_password">New Password</translate>
                </template>
                <template #label v-else>
                    <translate key="lang_edit_form_password">Password</translate>
                </template>

                <template #description v-if="isEditMode">
                    <translate key="lang_edit_form_password_desc">Leave blank to use the current password.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_publicKeys" :field="form.publicKeys"
                                  input-type="textarea">
                <template #label>
                    <translate key="lang_edit_form_publickeys">SSH Public Keys</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_publickeys_desc">Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.</translate>
                </template>
            </b-wrapped-form-group>

        </b-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'SftpUsersForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        isEditMode: Boolean
    },
};
</script>
