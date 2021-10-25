<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_username" :field="form.username">
                <template #label="{lang}">
                    <translate :key="lang">Username</translate>
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
                <template #label="{lang}">
                    <translate :key="lang">SSH Public Keys</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.</translate>
                </template>
            </b-wrapped-form-group>

        </b-form-row>
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
