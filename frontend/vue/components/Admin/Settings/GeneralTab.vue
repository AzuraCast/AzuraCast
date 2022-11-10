<template>
    <b-form-fieldset>
        <b-form-row>

            <b-wrapped-form-group class="col-md-6" id="edit_form_base_url" :field="form.base_url" input-type="url">
                <template #label="{lang}">
                    <translate :key="lang">Site Base URL</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_instance_name" :field="form.instance_name">
                <template #label="{lang}">
                    <translate :key="lang">AzuraCast Instance Name</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_prefer_browser_url"
                                     :field="form.prefer_browser_url">
                <template #label="{lang}">
                    <translate :key="lang">Prefer Browser URL (If Available)</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">If this setting is set to "Yes", the browser URL will be used instead of the base URL when it's available. Set to "No" to always use the base URL.</translate>
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_use_radio_proxy"
                                     :field="form.use_radio_proxy">
                <template #label="{lang}">
                    <translate :key="lang">Use Web Proxy for Radio</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">By default, radio stations broadcast on their own ports (i.e. 8000). If you're using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).</translate>
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-group class="col-md-6" id="edit_form_history_keep_days" :field="form.history_keep_days">
                <template #label="{lang}">
                    <translate :key="lang">Days of Playback History to Keep</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space.</translate>
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                        :options="historyKeepDaysOptions"></b-form-radio-group>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_enable_static_nowplaying"
                                     :field="form.enable_static_nowplaying">
                <template #label="{lang}">
                    <translate :key="lang">Use Static Files for Now Playing Updates</translate>
                </template>
                <template #description="{lang}">
                    <translate
                        :key="lang">Uses static JSON files to serve Now Playing data on public pages. This improves performance but will cause problems if you use multiple base URLs.</translate>
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox class="col-md-6" id="edit_form_enable_advanced_features"
                                     :field="form.enable_advanced_features">
                <template #label="{lang}">
                    <translate :key="lang">Enable Advanced Features</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">Enable certain advanced features in the web interface, including advanced playlist configuration, station port assignment, changing base media directories and other functionality that should only be used by users who are comfortable with advanced functionality.</translate>
                </template>
            </b-wrapped-form-checkbox>

        </b-form-row>
    </b-form-fieldset>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'SettingsGeneralTab',
    components: {BWrappedFormCheckbox, BFormFieldset, BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
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
