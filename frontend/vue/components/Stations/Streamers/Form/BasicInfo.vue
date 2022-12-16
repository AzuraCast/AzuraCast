<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_username" :field="form.streamer_username">
                    <template #label="{lang}">
                        {{ $gettext('Streamer Username') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The streamer will use this username to connect to the radio server.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_password" :field="form.streamer_password"
                                      input-type="password">
                    <template #label="{lang}">
                        {{ $gettext('Streamer password') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The streamer will use this password to connect to the radio server.') }}
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_display_name" :field="form.display_name">
                    <template #label="{lang}">
                        {{ $gettext('Streamer Display Name') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('This is the informal display name that will be shown in API responses if the streamer/DJ is live.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_comments" :field="form.comments"
                                      input-type="textarea">
                    <template #label="{lang}">
                        {{ $gettext('Comments') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Internal notes or comments about the user, visible only on this control panel.') }}
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
            <b-form-row class="mt-3">

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_is_active" :field="form.is_active">
                    <template #label="{lang}">
                        {{ $gettext('Account is Active') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Enable to allow this account to log in and stream.') }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_enforce_schedule"
                                         :field="form.enforce_schedule">
                    <template #label="{lang}">
                        {{ $gettext('Enforce Schedule Times') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('If enabled, this streamer will only be able to connect during their scheduled broadcast times.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'StreamerFormBasicInfo',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Basic Info');
        }
    }
};
</script>
