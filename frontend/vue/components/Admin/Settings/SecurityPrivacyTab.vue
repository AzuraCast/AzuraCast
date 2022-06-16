<template>
    <div>
        <b-form-fieldset>
            <template #label>
                <translate key="lang_privacy_hdr">Privacy</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_analytics" :field="form.analytics">
                    <template #label="{lang}">
                        <translate :key="lang">Listener Analytics Collection</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                            <b-form-radio value="all">
                                <b>
                                    <translate key="analytics_opt_full_title">Full:</translate>
                                </b>
                                <translate key="analytics_opt_full_desc">Collect aggregate listener statistics and IP-based listener statistics</translate>
                            </b-form-radio>
                            <b-form-radio value="no_ip">
                                <b>
                                    <translate key="analytics_opt_no_ip_title">Limited:</translate>
                                </b>
                                <translate
                                    key="analytics_opt_no_ip_desc">Only collect aggregate listener statistics</translate>
                            </b-form-radio>
                            <b-form-radio value="none">
                                <b>
                                    <translate key="analytics_opt_none_title">None:</translate>
                                </b>
                                <translate
                                    key="analytics_opt_none_desc">Do not collect any listener analytics</translate>
                            </b-form-radio>
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_security_hdr">Security</translate>
            </template>

            <b-form-row>
                <b-wrapped-form-checkbox class="col-md-12" id="edit_form_always_use_ssl"
                                         :field="form.always_use_ssl">
                    <template #label="{lang}">
                        <translate :key="lang">Always Use HTTPS</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure URL is visited.</translate>
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-12" id="edit_form_api_access_control"
                                      :field="form.api_access_control">
                    <template #label="{lang}">
                        <translate :key="lang">API "Access-Control-Allow-Origin" Header</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Set to * to allow all sources, or specify a list of origins separated by a comma (,).</translate>
                        <br>
                        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin"
                           target="_blank">
                            <translate :key="lang+'2'">Learn more about this header.</translate>
                        </a>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'SettingsSecurityPrivacyTab',
    components: {BWrappedFormCheckbox, BFormFieldset, BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
