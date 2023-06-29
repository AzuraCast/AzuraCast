<template>
    <b-form-group>
        <div class="row g-3">
            <b-wrapped-form-group
                id="edit_form_username"
                class="col-md-6"
                :field="form.username"
            >
                <template #label>
                    {{ $gettext('Username') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_password"
                class="col-md-6"
                :field="form.password"
                input-type="password"
            >
                <template
                    v-if="isEditMode"
                    #label
                >
                    {{ $gettext('New Password') }}
                </template>
                <template
                    v-else
                    #label
                >
                    {{ $gettext('Password') }}
                </template>

                <template
                    v-if="isEditMode"
                    #description
                >
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_publicKeys"
                class="col-md-12"
                :field="form.publicKeys"
                input-type="textarea"
            >
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

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    isEditMode: {
        type: Boolean,
        required: true
    }
});
</script>
