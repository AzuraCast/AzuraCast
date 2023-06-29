<template>
    <b-form-fieldset>
        <div class="row g-3">
            <b-wrapped-form-group
                id="edit_form_frontend_type"
                class="col-md-12"
                :field="form.frontend_type"
            >
                <template #label>
                    {{ $gettext('Broadcasting Service') }}
                </template>
                <template #description>
                    {{ $gettext('This software delivers your broadcast to the listening audience.') }}
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                        :options="frontendTypeOptions"
                    />
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset v-if="isLocalFrontend">
        <b-form-fieldset v-if="isShoutcastFrontend">
            <div class="row g-3">
                <b-wrapped-form-group
                    id="edit_form_frontend_sc_license_id"
                    class="col-md-6"
                    :field="form.frontend_config.sc_license_id"
                >
                    <template #label>
                        {{ $gettext('Shoutcast License ID') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_frontend_sc_user_id"
                    class="col-md-6"
                    :field="form.frontend_config.sc_user_id"
                >
                    <template #label>
                        {{ $gettext('Shoutcast User ID') }}
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>

        <b-form-fieldset>
            <div class="row g-3">
                <b-wrapped-form-group
                    id="edit_form_frontend_source_pw"
                    class="col-md-6"
                    :field="form.frontend_config.source_pw"
                >
                    <template #label>
                        {{ $gettext('Customize Source Password') }}
                    </template>
                    <template #description>
                        {{ $gettext('Leave blank to automatically generate a new password.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_frontend_admin_pw"
                    class="col-md-6"
                    :field="form.frontend_config.admin_pw"
                >
                    <template #label>
                        {{ $gettext('Customize Administrator Password') }}
                    </template>
                    <template #description>
                        {{ $gettext('Leave blank to automatically generate a new password.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    v-if="showAdvanced"
                    id="edit_form_frontend_port"
                    class="col-md-6"
                    :field="form.frontend_config.port"
                    input-type="number"
                    :input-attrs="{min: '0'}"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Customize Broadcasting Port') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('No other program can be using this port. Leave blank to automatically assign a port.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    v-if="showAdvanced"
                    id="edit_form_max_listeners"
                    class="col-md-6"
                    :field="form.frontend_config.max_listeners"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Maximum Listeners') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Maximum number of total listeners across all streams. Leave blank to use the default.')
                        }}
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>

        <b-form-fieldset v-if="showAdvanced">
            <div class="row g-3">
                <div class="col-md-5">
                    <b-wrapped-form-group
                        id="edit_form_frontend_banned_ips"
                        :field="form.frontend_config.banned_ips"
                        input-type="textarea"
                        :input-attrs="{class: 'text-preformatted'}"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Banned IP Addresses') }}
                        </template>
                        <template #description>
                            {{ $gettext('List one IP address or group (in CIDR format) per line.') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="edit_form_frontend_allowed_ips"
                        :field="form.frontend_config.allowed_ips"
                        input-type="textarea"
                        :input-attrs="{class: 'text-preformatted'}"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Allowed IP Addresses') }}
                        </template>
                        <template #description>
                            {{ $gettext('List one IP address or group (in CIDR format) per line.') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="edit_form_frontend_banned_user_agents"
                        :field="form.frontend_config.banned_user_agents"
                        input-type="textarea"
                        :input-attrs="{class: 'text-preformatted'}"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Banned User Agents') }}
                        </template>
                        <template #description>
                            {{ $gettext('List one user agent per line. Wildcards (*) are allowed.') }}
                        </template>
                    </b-wrapped-form-group>
                </div>

                <b-wrapped-form-group
                    id="edit_form_frontend_banned_countries"
                    class="col-md-7"
                    :field="form.frontend_config.banned_countries"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Banned Countries') }}
                    </template>
                    <template #description>
                        {{ $gettext('Select the countries that are not allowed to connect to the streams.') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-select
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :options="countryOptions"
                            style="min-height: 300px;"
                            multiple
                        />

                        <button
                            class="btn btn-block btn-primary"
                            @click.prevent="clearCountries"
                        >
                            {{ $gettext('Clear List') }}
                        </button>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>

        <b-form-fieldset v-if="showAdvanced">
            <template #label>
                {{ $gettext('Custom Configuration') }}
            </template>
            <template #description>
                {{ $gettext('This code will be included in the frontend configuration. Allowed formats are:') }}
                <ul>
                    <li>JSON: <code>{"new_key": "new_value"}</code></li>
                    <li>XML: <code>&lt;new_key&gt;new_value&lt;/new_key&gt;</code></li>
                </ul>
            </template>

            <div class="row g-3">
                <b-wrapped-form-group
                    id="edit_form_frontend_custom_config"
                    class="col-md-12"
                    :field="form.frontend_config.custom_config"
                    input-type="textarea"
                    :input-attrs="{class: 'text-preformatted', spellcheck: 'false', 'max-rows': 25, rows: 5}"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Custom Configuration') }}
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>
    </b-form-fieldset>
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {FRONTEND_ICECAST, FRONTEND_REMOTE, FRONTEND_SHOUTCAST} from "~/components/Entity/RadioAdapters";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

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
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const {$gettext} = useTranslate();

const frontendTypeOptions = computed(() => {
    let frontendOptions = [
        {
            text: $gettext('Use Icecast 2.4 on this server.'),
            value: FRONTEND_ICECAST
        },
    ];

    if (props.isShoutcastInstalled) {
        frontendOptions.push({
            text: $gettext('Use Shoutcast DNAS 2 on this server.'),
            value: FRONTEND_SHOUTCAST
        });
    }

    frontendOptions.push({
        text: $gettext('Only connect to a remote server.'),
        value: FRONTEND_REMOTE
    });

    return frontendOptions;
});

const countryOptions = computed(() => {
    return objectToFormOptions(props.countries);
});

const isLocalFrontend = computed(() => {
    return props.form.frontend_type.$model !== FRONTEND_REMOTE;
});

const isShoutcastFrontend = computed(() => {
    return props.form.frontend_type.$model === FRONTEND_SHOUTCAST;
});

const clearCountries = () => {
    props.form.frontend_config.banned_countries.$model = [];
}
</script>
