<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_type" :field="form.type">
                    <template #label>
                        <translate key="lang_edit_form_type">Remote Station Type</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group
                            stacked
                            :id="props.id"
                            :state="props.state"
                            v-model="props.field.$model"
                            :options="typeOptions"
                        ></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_display_name" :field="form.display_name">
                    <template #label>
                        <translate key="lang_edit_form_display_name">Display Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_display_name_desc">The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_url" :field="form.url">
                    <template #label>
                        <translate key="lang_edit_form_url">Remote Station Listening URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_url_desc">Example: if the remote radio URL is http://station.example.com:8000/radio.mp3, enter "http://station.example.com:8000".</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mount" :field="form.mount">
                    <template #label>
                        <translate key="lang_edit_form_mount">Remote Station Listening Mountpoint/SID</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_mount_desc">Specify a mountpoint (i.e. "/radio.mp3") or a Shoutcast SID (i.e. "2") to specify a specific stream to use for statistics or broadcasting.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_admin_password" :field="form.admin_password">
                    <template #label>
                        <translate key="lang_edit_form_admin_password">Remote Station Administrator Password</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_admin_password_desc">To retrieve detailed unique listeners and client details, an administrator password is often required.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_is_visible_on_public_pages"
                                      :field="form.is_visible_on_public_pages">
                    <template #description>
                        <translate key="lang_edit_form_is_visible_on_public_pages_desc">Enable to allow listeners to select this relay on this station's public pages.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id"
                                         v-model="props.field.$model">
                            <translate key="lang_edit_form_is_visible_on_public_pages">Show on Public Pages</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import {REMOTE_ICECAST, REMOTE_SHOUTCAST1, REMOTE_SHOUTCAST2} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'RemoteFormBasicInfo',
    components: {BWrappedFormGroup},
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
