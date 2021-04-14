<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="edit_form_streamer_username">
                    <template v-slot:label>
                        <translate key="lang_edit_form_streamer_username">Streamer Username</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_edit_form_streamer_username_desc">The streamer will use this username to connect to the radio server.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_streamer_username" v-model="form.streamer_username.$model"
                                  :state="form.streamer_username.$dirty ? !form.streamer_username.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_streamer_password">
                    <template v-slot:label>
                        <translate key="lang_edit_form_streamer_password">Streamer password</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_edit_form_streamer_password_desc">The streamer will use this password to connect to the radio server.</translate>
                    </template>
                    <b-form-input type="password" id="edit_form_streamer_password" v-model="form.streamer_password.$model"
                                  :state="form.streamer_password.$dirty ? !form.streamer_password.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
            </b-row>
            <b-row>
                <b-form-group class="col-md-6" label-for="edit_form_display_name">
                    <template v-slot:label>
                        <translate key="lang_edit_form_display_name">Streamer Display Name</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_edit_form_display_name_desc">This is the informal display name that will be shown in API responses if the streamer/DJ is live.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_display_name" v-model="form.display_name.$model"
                                  :state="form.display_name.$dirty ? !form.display_name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_comments">
                    <template v-slot:label>
                        <translate key="lang_edit_form_comments">Comments</translate>
                    </template>
                    <template v-slot:description>
                        <translate key="lang_edit_form_comments_desc">Internal notes or comments about the user, visible only on this control panel.</translate>
                    </template>
                    <b-form-textarea id="edit_form_comments" v-model="form.comments.$model"
                                     :state="form.comments.$dirty ? !form.comments.$error : null"></b-form-textarea>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
            </b-row>
            <b-row class="mt-3">
                <b-form-group class="col-md-6" label-for="form_edit_is_active">
                    <template v-slot:description>
                        <translate key="lang_form_edit_is_active_desc">Enable to allow this account to log in and stream.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_is_active" v-model="form.is_active.$model">
                        <translate key="lang_form_edit_is_active">Account is Active</translate>
                    </b-form-checkbox>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="form_edit_enforce_schedule">
                    <template v-slot:description>
                        <translate key="lang_form_edit_enforce_schedule_desc">If enabled, this streamer will only be able to connect during their scheduled broadcast times.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_enforce_schedule" v-model="form.enforce_schedule.$model">
                        <translate key="lang_form_edit_enforce_schedule">Enforce Schedule Times</translate>
                    </b-form-checkbox>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'StreamerFormBasicInfo',
    props: {
        form: Object
    },
    computed: {
        langTabTitle () {
            return this.$gettext('Basic Info');
        }
    }
};
</script>
