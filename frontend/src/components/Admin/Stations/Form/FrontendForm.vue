<template>
    <tab
        :label="$gettext('Broadcasting')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-multi-check
                id="edit_form_frontend_type"
                class="col-md-12"
                :field="v$.frontend_type"
                :options="frontendTypeOptions"
                stacked
                radio
                :label="$gettext('Broadcasting Service')"
                :description="$gettext('This software delivers your broadcast to the listening audience.')"
            />
        </div>

        <template v-if="isLocalFrontend">
            <div
                v-if="isShoutcastFrontend"
                class="row g-3 mb-3"
            >
                <form-group-field
                    id="edit_form_frontend_sc_license_id"
                    class="col-md-6"
                    :field="v$.frontend_config.sc_license_id"
                    :label="$gettext('Shoutcast License ID')"
                />

                <form-group-field
                    id="edit_form_frontend_sc_user_id"
                    class="col-md-6"
                    :field="v$.frontend_config.sc_user_id"
                    :label="$gettext('Shoutcast User ID')"
                />
            </div>

            <div class="row g-3 mb-3">
                <form-group-field
                    id="edit_form_frontend_source_pw"
                    class="col-md-6"
                    :field="v$.frontend_config.source_pw"
                    :label="$gettext('Customize Source Password')"
                    :description="$gettext('Leave blank to automatically generate a new password.')"
                />

                <form-group-field
                    id="edit_form_frontend_admin_pw"
                    class="col-md-6"
                    :field="v$.frontend_config.admin_pw"
                    :label="$gettext('Customize Administrator Password')"
                    :description="$gettext('Leave blank to automatically generate a new password.')"
                />
            </div>

            <form-fieldset v-if="enableAdvancedFeatures">
                <template #label>
                    {{ $gettext('Advanced Configuration') }}
                    <span class="badge small text-bg-primary ms-2">
                        {{ $gettext('Advanced') }}
                    </span>
                </template>

                <div class="row g-3 mb-3">
                    <form-group-field
                        id="edit_form_frontend_port"
                        class="col-md-6"
                        :field="v$.frontend_config.port"
                        input-type="number"
                        :input-attrs="{min: '0'}"
                        :label="$gettext('Customize Broadcasting Port')"
                        :description="$gettext('No other program can be using this port. Leave blank to automatically assign a port.')"
                    />

                    <form-group-field
                        id="edit_form_max_listeners"
                        class="col-md-6"
                        :field="v$.frontend_config.max_listeners"
                        :label="$gettext('Maximum Listeners')"
                        :description="$gettext('Maximum number of total listeners across all streams. Leave blank to use the default.')"
                    />
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <form-group-field
                            id="edit_form_frontend_banned_ips"
                            :field="v$.frontend_config.banned_ips"
                            input-type="textarea"
                            :input-attrs="{class: 'text-preformatted'}"
                            :label="$gettext('Banned IP Addresses')"
                            :description="$gettext('List one IP address or group (in CIDR format) per line.')"
                        />

                        <form-group-field
                            id="edit_form_frontend_allowed_ips"
                            :field="v$.frontend_config.allowed_ips"
                            input-type="textarea"
                            :input-attrs="{class: 'text-preformatted'}"
                            :label="$gettext('Allowed IP Addresses')"
                            :description="$gettext('List one IP address or group (in CIDR format) per line.')"
                        />

                        <form-group-field
                            id="edit_form_frontend_banned_user_agents"
                            :field="v$.frontend_config.banned_user_agents"
                            input-type="textarea"
                            :input-attrs="{class: 'text-preformatted'}"
                            :label="$gettext('Banned User Agents')"
                            :description="$gettext('List one user agent per line. Wildcards (*) are allowed.')"
                        />
                    </div>

                    <div class="col-md-7">
                        <form-group-select
                            id="edit_form_frontend_banned_countries"
                            :field="v$.frontend_config.banned_countries"
                            :options="countryOptions"
                            multiple
                            :label="$gettext('Banned Countries')"
                            :description="$gettext('Select the countries that are not allowed to connect to the streams.')"
                        />

                        <div class="block-buttons">
                            <button
                                type="button"
                                class="btn btn-block btn-primary"
                                @click="clearCountries"
                            >
                                {{ $gettext('Clear List') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form-fieldset>

            <form-fieldset v-if="enableAdvancedFeatures">
                <template #label>
                    {{ $gettext('Custom Configuration') }}
                    <span class="badge small text-bg-primary ms-2">
                        {{ $gettext('Advanced') }}
                    </span>
                </template>
                <template #description>
                    {{ $gettext('This code will be included in the frontend configuration. Allowed formats are:') }}
                    <ul>
                        <li>JSON: <code>{"new_key": "new_value"}</code></li>
                        <li>XML: <code>&lt;new_key&gt;new_value&lt;/new_key&gt;</code></li>
                    </ul>
                </template>

                <div class="row g-3">
                    <form-group-field
                        id="edit_form_frontend_custom_config"
                        class="col-md-12"
                        :field="v$.frontend_config.custom_config"
                        input-type="textarea"
                        :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}"
                        :label="$gettext('Custom Configuration')"
                    />
                </div>
            </form-fieldset>
        </template>
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {FrontendAdapter} from "~/entities/RadioAdapters";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {numeric, required} from "@vuelidate/validators";
import {useAzuraCast} from "~/vendor/azuracast";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    isShoutcastInstalled: {
        type: Boolean,
        default: false
    },
    countries: {
        type: Object,
        required: true
    }
});

const {enableAdvancedFeatures} = useAzuraCast();

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    computed(() => {
        let validations: {
            [key: string | number]: any
        } = {
            frontend_type: {required},
            frontend_config: {
                sc_license_id: {},
                sc_user_id: {},
                source_pw: {},
                admin_pw: {},
            },
        };

        if (enableAdvancedFeatures) {
            validations = {
                ...validations,
                frontend_config: {
                    ...validations.frontend_config,
                    port: {numeric},
                    max_listeners: {},
                    custom_config: {},
                    banned_ips: {},
                    banned_countries: {},
                    allowed_ips: {},
                    banned_user_agents: {}
                },
            };
        }

        return validations;
    }),
    form,
    () => {
        let blankForm: {
            [key: string | number]: any
        } = {
            frontend_type: FrontendAdapter.Icecast,
            frontend_config: {
                sc_license_id: '',
                sc_user_id: '',
                source_pw: '',
                admin_pw: '',
            },
        };

        if (enableAdvancedFeatures) {
            blankForm = {
                ...blankForm,
                frontend_config: {
                    ...blankForm.frontend_config,
                    port: '',
                    max_listeners: '',
                    custom_config: '',
                    banned_ips: '',
                    banned_countries: [],
                    allowed_ips: '',
                    banned_user_agents: '',
                },
            };
        }

        return blankForm;
    }
);

const {$gettext} = useTranslate();

const frontendTypeOptions = computed(() => {
    const frontendOptions = [
        {
            text: $gettext('Use Icecast 2.4 on this server.'),
            value: FrontendAdapter.Icecast
        },
    ];

    if (props.isShoutcastInstalled) {
        frontendOptions.push({
            text: $gettext('Use Shoutcast DNAS 2 on this server.'),
            value: FrontendAdapter.Shoutcast
        });
    }

    frontendOptions.push({
        text: $gettext('Only connect to a remote server.'),
        value: FrontendAdapter.Remote
    });

    return frontendOptions;
});

const countryOptions = computed(() => {
    return objectToFormOptions(props.countries);
});

const isLocalFrontend = computed(() => {
    return form.value.frontend_type !== FrontendAdapter.Remote;
});

const isShoutcastFrontend = computed(() => {
    return form.value.frontend_type === FrontendAdapter.Shoutcast;
});

const clearCountries = () => {
    form.value.frontend_config.banned_countries = [];
}
</script>
