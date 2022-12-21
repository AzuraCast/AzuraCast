<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="edit_form_username" :field="form.username">
                <template #label>
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
                <template #label>
                    {{ $gettext('SSH Public Keys') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.')
                    }}
                </template>
            </b-wrapped-form-group>

        </div>
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
