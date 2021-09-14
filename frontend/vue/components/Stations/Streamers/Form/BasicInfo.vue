<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_username" :field="form.streamer_username">
                    <template #label>
                        <translate key="lang_edit_form_streamer_username">Streamer Username</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_streamer_username_desc">The streamer will use this username to connect to the radio server.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_streamer_password" :field="form.streamer_password">
                    <template #label>
                        <translate key="lang_edit_form_streamer_password">Streamer password</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_streamer_password_desc">The streamer will use this password to connect to the radio server.</translate>
                    </template>
                    <template #default="props">
                        <b-form-input type="password" :id="props.id" v-model="props.field.$model"
                                      :state="props.state"></b-form-input>
                    </template>
                </b-wrapped-form-group>

            </b-row>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_display_name" :field="form.display_name">
                    <template #label>
                        <translate key="lang_edit_form_display_name">Streamer Display Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_display_name_desc">This is the informal display name that will be shown in API responses if the streamer/DJ is live.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_comments" :field="form.comments">
                    <template #label>
                        <translate key="lang_edit_form_comments">Comments</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_comments_desc">Internal notes or comments about the user, visible only on this control panel.</translate>
                    </template>
                    <template #default="props">
                        <b-form-textarea :id="props.id" v-model="props.field.$model"
                                         :state="props.state"></b-form-textarea>
                    </template>
                </b-wrapped-form-group>

            </b-row>
            <b-row class="mt-3">

                <b-wrapped-form-group class="col-md-6" id="form_edit_is_active" :field="form.is_active">
                    <template #description>
                        <translate key="lang_form_edit_is_active_desc">Enable to allow this account to log in and stream.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_is_active">Account is Active</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_enforce_schedule" :field="form.enforce_schedule">
                    <template #description>
                        <translate key="lang_form_edit_enforce_schedule_desc">If enabled, this streamer will only be able to connect during their scheduled broadcast times.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_enforce_schedule">Enforce Schedule Times</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'StreamerFormBasicInfo',
    components: {BWrappedFormGroup},
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
