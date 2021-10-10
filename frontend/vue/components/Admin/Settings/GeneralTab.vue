<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_base_url" :field="form.base_url" input-type="url">
                    <template #label>
                        <translate key="lang_edit_form_base_url">Site Base URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_base_url_desc">The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_instance_name" :field="form.instance_name">
                    <template #label>
                        <translate key="lang_edit_form_instance_name">AzuraCast Instance Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_instance_name_desc">This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_prefer_browser_url"
                                      :field="form.prefer_browser_url">
                    <template #description>
                        <translate key="lang_edit_form_prefer_browser_url_desc">If this setting is set to "Yes", the browser URL will be used instead of the base URL when it's available. Set to "No" to always use the base URL.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_prefer_browser_url">Prefer Browser URL (If Available)</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_use_radio_proxy"
                                      :field="form.use_radio_proxy">
                    <template #description>
                        <translate key="lang_edit_form_use_radio_proxy_desc">By default, radio stations broadcast on their own ports (i.e. 8000). If you're using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_use_radio_proxy">Use Web Proxy for Radio</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_history_keep_days" :field="form.history_keep_days">
                    <template #label>
                        <translate key="lang_edit_form_history_keep_days">Days of Playback History to Keep</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_history_keep_days_desc">Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space.</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                            :options="historyKeepDaysOptions"></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_enable_websockets"
                                      :field="form.enable_websockets">
                    <template #description>
                        <translate key="lang_edit_form_enable_websockets_desc">Enables or disables the use of the newer and faster WebSocket-based system for receiving live updates on public players. You may need to disable this if you encounter problems with it.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_websockets">Use WebSockets for Now Playing Updates</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_enable_advanced_features"
                                      :field="form.enable_websockets">
                    <template #description>
                        <translate key="lang_edit_form_enable_advanced_features_desc">Enable certain advanced features in the web interface, including advanced playlist configuration, station port assignment, changing base media directories and other functionality that should only be used by users who are comfortable with advanced functionality.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_advanced_features">Enable Advanced Features</translate>
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
    name: 'SettingsGeneralTab',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Settings');
        },
        historyKeepDaysOptions() {
            return [
                {
                    value: 14,
                    text: this.$gettext('Last 14 Days')
                },
                {
                    value: 30,
                    text: this.$gettext('Last 30 Days')
                },
                {
                    value: 60,
                    text: this.$gettext('Last 60 Days')
                },
                {
                    value: 365,
                    text: this.$gettext('Last Year')
                },
                {
                    value: 730,
                    text: this.$gettext('Last 2 Years')
                },
                {
                    value: 0,
                    text: this.$gettext('Indefinitely')
                },
            ]
        },
    }
}
</script>
