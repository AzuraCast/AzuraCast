<template>
    <b-tab :title="langTabTitle" :title-link-class="tabClass">
        <b-form-fieldset>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_frontend_type"
                                      :field="form.frontend_type">
                    <template #label="{lang}">
                        <translate :key="lang">Broadcasting Service</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">This software delivers your broadcast to the listening audience.</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="frontendTypeOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset v-if="isLocalFrontend">
            <b-form-fieldset v-if="isShoutcastFrontend">
                <b-form-row>
                    <b-wrapped-form-group class="col-md-6" id="edit_form_frontend_sc_license_id"
                                          :field="form.frontend_config.sc_license_id">
                        <template #label="{lang}">
                            <translate :key="lang">SHOUTcast License ID</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_frontend_sc_user_id"
                                          :field="form.frontend_config.sc_user_id">
                        <template #label="{lang}">
                            <translate :key="lang">SHOUTcast User ID</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>

            <b-form-fieldset>
                <b-form-row>
                    <b-wrapped-form-group class="col-md-6" id="edit_form_frontend_source_pw"
                                          :field="form.frontend_config.source_pw">
                        <template #label="{lang}">
                            <translate :key="lang">Customize Source Password</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Leave blank to automatically generate a new password.</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_frontend_admin_pw"
                                          :field="form.frontend_config.admin_pw">
                        <template #label="{lang}">
                            <translate :key="lang">Customize Administrator Password</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Leave blank to automatically generate a new password.</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group v-if="showAdvanced" class="col-md-6" id="edit_form_frontend_port"
                                          :field="form.frontend_config.port" input-type="number"
                                          :input-attrs="{min: '0'}" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Customize Broadcasting Port</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">No other program can be using this port. Leave blank to automatically assign a port.</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group v-if="showAdvanced" class="col-md-6" id="edit_form_max_listeners"
                                          :field="form.frontend_config.max_listeners" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Maximum Listeners</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Maximum number of total listeners across all streams. Leave blank to use the default.</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>

            <b-form-fieldset v-if="showAdvanced">
                <b-form-row>
                    <b-col md="5">
                        <b-wrapped-form-group id="edit_form_frontend_banned_ips"
                                              :field="form.frontend_config.banned_ips" input-type="textarea"
                                              :input-attrs="{class: 'text-preformatted'}" advanced>
                            <template #label="{lang}">
                                <translate :key="lang">Banned IP Addresses</translate>
                            </template>
                            <template #description="{lang}">
                                <translate
                                    :key="lang">List one IP address or group (in CIDR format) per line.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group id="edit_form_frontend_allowed_ips"
                                              :field="form.frontend_config.allowed_ips" input-type="textarea"
                                              :input-attrs="{class: 'text-preformatted'}" advanced>
                            <template #label="{lang}">
                                <translate :key="lang">Allowed IP Addresses</translate>
                            </template>
                            <template #description="{lang}">
                                <translate
                                    :key="lang">List one IP address or group (in CIDR format) per line.</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-col>

                    <b-wrapped-form-group class="col-md-7" id="edit_form_frontend_banned_countries"
                                          :field="form.frontend_config.banned_countries"
                                          advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Banned Countries</translate>
                        </template>
                        <template #description="{lang}">
                            <translate
                                :key="lang">Select the countries that are not allowed to connect to the streams.</translate>
                        </template>
                        <template #default="props">
                            <b-form-select :id="props.id" v-model="props.field.$model"
                                           :options="countryOptions" style="min-height: 200px;"
                                           multiple></b-form-select>

                            <b-button block variant="outline-primary" @click.prevent="clearCountries">
                                <translate key="lang_btn_clear_countries">Clear List</translate>
                            </b-button>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>

            <b-form-fieldset v-if="showAdvanced">
                <template #label>
                    <translate key="lang_hdr_custom_config">Custom Configuration</translate>
                </template>
                <template #description>
                    <translate key="lang_custom_config_1">This code will be included in the frontend configuration. Allowed formats are:</translate>
                    <ul>
                        <li>JSON: <code>{"new_key": "new_value"}</code></li>
                        <li>XML: <code>&lt;new_key&gt;new_value&lt;/new_key&gt;</code></li>
                    </ul>
                </template>

                <b-form-row>
                    <b-wrapped-form-group class="col-md-12" id="edit_form_frontend_custom_config"
                                          :field="form.frontend_config.custom_config" input-type="textarea"
                                          :input-attrs="{class: 'text-preformatted', style: 'min-height: 250px;'}"
                                          advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Custom Configuration</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>
        </b-form-fieldset>
    </b-tab>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {FRONTEND_ICECAST, FRONTEND_REMOTE, FRONTEND_SHOUTCAST} from "~/components/Entity/RadioAdapters";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AdminStationsFrontendForm',
    components: {BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        tabClass: {},
        isShoutcastInstalled: {
            type: Boolean,
            default: false
        },
        countries: Object,
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Broadcasting');
        },
        frontendTypeOptions() {
            let frontendOptions = [
                {
                    text: this.$gettext('Use Icecast 2.4 on this server.'),
                    value: FRONTEND_ICECAST
                },
            ];

            if (this.isShoutcastInstalled) {
                frontendOptions.push({
                    text: this.$gettext('Use SHOUTcast DNAS 2 on this server.'),
                    value: FRONTEND_SHOUTCAST
                });
            }

            frontendOptions.push({
                text: this.$gettext('Only connect to a remote server.'),
                value: FRONTEND_REMOTE
            });

            return frontendOptions;
        },
        countryOptions() {
            return objectToFormOptions(this.countries);
        },
        isLocalFrontend() {
            return this.form.frontend_type.$model !== FRONTEND_REMOTE;
        },
        isShoutcastFrontend() {
            return this.form.frontend_type.$model === FRONTEND_SHOUTCAST;
        }
    },
    methods: {
        clearCountries() {
            this.form.frontend_config.banned_countries.$model = [];
        }
    }
}
</script>
