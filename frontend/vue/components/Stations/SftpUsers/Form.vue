<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_username" :field="form.username">
                <template #label="{lang}">
                    {{ $gettext('Username') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_password" :field="form.password"
                                  input-type="password">
                <template #label v-if="isEditMode">
                    {{ $gettext('New Password') }}
                </template>
                <template #label v-else>
                    {{ $gettext('Password') }}
                </template>

                <template #description v-if="isEditMode">
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_publicKeys" :field="form.publicKeys"
                                  input-type="textarea">
                <template #label="{lang}">
                    {{ $gettext('SSH Public Keys') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.') }}
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
