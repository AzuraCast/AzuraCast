<template>
    <b-tab
        :title="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="form-row">
                <b-wrapped-form-group
                    id="edit_form_streamer_username"
                    class="col-md-6"
                    :field="form.streamer_username"
                >
                    <template #label>
                        {{ $gettext('Streamer Username') }}
                    </template>
                    <template #description>
                        {{ $gettext('The streamer will use this username to connect to the radio server.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_streamer_password"
                    class="col-md-6"
                    :field="form.streamer_password"
                    input-type="password"
                >
                    <template #label>
                        {{ $gettext('Streamer password') }}
                    </template>
                    <template #description>
                        {{ $gettext('The streamer will use this password to connect to the radio server.') }}
                    </template>
                </b-wrapped-form-group>
            </div>
            <div class="form-row">
                <b-wrapped-form-group
                    id="edit_form_display_name"
                    class="col-md-6"
                    :field="form.display_name"
                >
                    <template #label>
                        {{ $gettext('Streamer Display Name') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This is the informal display name that will be shown in API responses if the streamer/DJ is live.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_comments"
                    class="col-md-6"
                    :field="form.comments"
                    input-type="textarea"
                >
                    <template #label>
                        {{ $gettext('Comments') }}
                    </template>
                    <template #description>
                        {{ $gettext('Internal notes or comments about the user, visible only on this control panel.') }}
                    </template>
                </b-wrapped-form-group>
            </div>
            <div class="form-row mt-3">
                <b-wrapped-form-checkbox
                    id="form_edit_is_active"
                    class="col-md-6"
                    :field="form.is_active"
                >
                    <template #label>
                        {{ $gettext('Account is Active') }}
                    </template>
                    <template #description>
                        {{ $gettext('Enable to allow this account to log in and stream.') }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox
                    id="form_edit_enforce_schedule"
                    class="col-md-6"
                    :field="form.enforce_schedule"
                >
                    <template #label>
                        {{ $gettext('Enforce Schedule Times') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If enabled, this streamer will only be able to connect during their scheduled broadcast times.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

const props = defineProps({
    form: Object
});
</script>
