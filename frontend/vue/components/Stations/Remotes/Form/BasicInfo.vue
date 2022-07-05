<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_type" :field="form.type">
                    <template #label="{lang}">
                        <translate :key="lang">Remote Station Type</translate>
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
                    <template #label="{lang}">
                        <translate :key="lang">Display Name</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_url" :field="form.url">
                    <template #label="{lang}">
                        <translate :key="lang">Remote Station Listening URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Example: if the remote radio URL is http://station.example.com:8000/radio.mp3, enter "http://station.example.com:8000".</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_mount" :field="form.mount">
                    <template #label="{lang}">
                        <translate :key="lang">Remote Station Listening Mountpoint/SID</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Specify a mountpoint (i.e. "/radio.mp3") or a Shoutcast SID (i.e. "2") to specify a specific stream to use for statistics or broadcasting.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_admin_password" :field="form.admin_password">
                    <template #label="{lang}">
                        <translate :key="lang">Remote Station Administrator Password</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">To retrieve detailed unique listeners and client details, an administrator password is often required.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_visible_on_public_pages"
                                         :field="form.is_visible_on_public_pages">
                    <template #label="{lang}">
                        <translate :key="lang">Show on Public Pages</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Enable to allow listeners to select this relay on this station's public pages.</translate>
                    </template>
                </b-wrapped-form-checkbox>

            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import {REMOTE_ICECAST, REMOTE_SHOUTCAST1, REMOTE_SHOUTCAST2} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'RemoteFormBasicInfo',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
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
                    text: 'Shoutcast v1',
                },
                {
                    value: REMOTE_SHOUTCAST2,
                    text: 'Shoutcast v2',
                }
            ];
        },
    }
};
</script>
