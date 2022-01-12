<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_username" :field="form.streamer_username">
                    <template #label="{lang}">
                        <translate :key="lang">Streamer Username</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">The streamer will use this username to connect to the radio server.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_password" :field="form.streamer_password"
                                      input-type="password">
                    <template #label="{lang}">
                        <translate :key="lang">Streamer password</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">The streamer will use this password to connect to the radio server.</translate>
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_display_name" :field="form.display_name">
                    <template #label="{lang}">
                        <translate :key="lang">Streamer Display Name</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">This is the informal display name that will be shown in API responses if the streamer/DJ is live.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_comments" :field="form.comments"
                                      input-type="textarea">
                    <template #label="{lang}">
                        <translate :key="lang">Comments</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Internal notes or comments about the user, visible only on this control panel.</translate>
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
            <b-form-row class="mt-3">

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_is_active" :field="form.is_active">
                    <template #label="{lang}">
                        <translate :key="lang">Account is Active</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Enable to allow this account to log in and stream.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_enforce_schedule"
                                         :field="form.enforce_schedule">
                    <template #label="{lang}">
                        <translate :key="lang">Enforce Schedule Times</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">If enabled, this streamer will only be able to connect during their scheduled broadcast times.</translate>
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
