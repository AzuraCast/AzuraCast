<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-12" label-for="edit_form_type">
                    <template #label>
                        <translate key="lang_edit_form_type">Remote Station Type</translate>
                    </template>
                    <b-form-radio-group
                        stacked
                        id="edit_form_type"
                        v-model="form.type.$model"
                        :options="typeOptions"
                    ></b-form-radio-group>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_display_name">
                    <template #label>
                        <translate key="lang_edit_form_display_name">Display Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_display_name_desc">The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_display_name" v-model="form.display_name.$model"
                                  :state="form.display_name.$dirty ? !form.display_name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_url">
                    <template #label>
                        <translate key="lang_edit_form_url">Remote Station Listening URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_url_desc">Example: if the remote radio URL is http://station.example.com:8000/radio.mp3, enter <code>http://station.example.com:8000</code>.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_url" v-model="form.url.$model"
                                  :state="form.url.$dirty ? !form.url.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_mount">
                    <template #label>
                        <translate key="lang_edit_form_mount">Remote Station Listening Mountpoint/SID</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_mount_desc">Specify a mountpoint (i.e. <code>/radio.mp3</code>) or a Shoutcast SID (i.e. <code>2</code>) to specify a specific stream to use for statistics or broadcasting.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_mount" v-model="form.mount.$model"
                                  :state="form.mount.$dirty ? !form.mount.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_admin_password">
                    <template #label>
                        <translate key="lang_edit_form_admin_password">Remote Station Administrator Password</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_admin_password_desc">To retrieve detailed unique listeners and client details, an administrator password is often required.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_admin_password" v-model="form.admin_password.$model"
                                  :state="form.admin_password.$dirty ? !form.admin_password.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_is_visible_on_public_pages">
                    <template #description>
                        <translate key="lang_edit_form_is_visible_on_public_pages_desc">Enable to allow listeners to select this relay on this station's public pages.</translate>
                    </template>
                    <b-form-checkbox id="edit_form_is_visible_on_public_pages"
                                     v-model="form.is_visible_on_public_pages.$model">
                        <translate key="lang_edit_form_is_visible_on_public_pages">Show on Public Pages</translate>
                    </b-form-checkbox>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import {REMOTE_ICECAST, REMOTE_SHOUTCAST1, REMOTE_SHOUTCAST2} from '../../../Entity/RadioAdapters';

export default {
    name: 'RemoteFormBasicInfo',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Basic Info');
        },
        typeOptions() {
            return [
                {
                    value: REMOTE_ICECAST,
                    text: 'Icecast v2.4+',
                },
                {
                    value: REMOTE_SHOUTCAST1,
                    text: 'SHOUTcast v1',
                },
                {
                    value: REMOTE_SHOUTCAST2,
                    text: 'SHOUTcast v2',
                }
            ];
        },
    }
};
</script>
