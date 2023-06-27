<template>
    <b-form-fieldset>
        <div class="form-row">
            <b-wrapped-form-group
                id="edit_form_base_url"
                class="col-md-6"
                :field="form.base_url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Site Base URL') }}
                </template>
                <template #description>
                    {{
                        $gettext('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_instance_name"
                class="col-md-6"
                :field="form.instance_name"
            >
                <template #label>
                    {{ $gettext('BoostCast Instance Name') }}
                </template>
                <template #description>
                    {{
                        $gettext('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox
                id="edit_form_prefer_browser_url"
                class="col-md-6"
                :field="form.prefer_browser_url"
            >
                <template #label>
                    {{ $gettext('Prefer Browser URL (If Available)') }}
                </template>
                <template #description>
                    {{
                        $gettext('If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.')
                    }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox
                id="edit_form_use_radio_proxy"
                class="col-md-6"
                :field="form.use_radio_proxy"
            >
                <template #label>
                    {{ $gettext('Use Web Proxy for Radio') }}
                </template>
                <template #description>
                    {{
                        $gettext('By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).')
                    }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-group
                id="edit_form_history_keep_days"
                class="col-md-6"
                :field="form.history_keep_days"
            >
                <template #label>
                    {{ $gettext('Days of Playback History to Keep') }}
                </template>
                <template #description>
                    {{
                        $gettext('Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                        :options="historyKeepDaysOptions"
                    />
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-checkbox
                id="edit_form_enable_static_nowplaying"
                class="col-md-6"
                :field="form.enable_static_nowplaying"
            >
                <template #label>
                    {{ $gettext('Use High-Performance Now Playing Updates') }}
                </template>
                <template #description>
                    {{
                        $gettext('Uses either Websockets, Server-Sent Events (SSE) or static JSON files to serve Now Playing data on public pages. This improves performance, especially with large listener volume. Disable this if you are encountering problems with the service or use multiple URLs to serve your public pages.')
                    }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox
                id="edit_form_enable_advanced_features"
                class="col-md-6"
                :field="form.enable_advanced_features"
            >
                <template #label>
                    {{ $gettext('Enable Advanced Features') }}
                </template>
                <template #description>
                    {{
                        $gettext('Enable certain advanced features in the web interface, including advanced playlist configuration, station port assignment, changing base media directories and other functionality that should only be used by users who are comfortable with advanced functionality.')
                    }}
                </template>
            </b-wrapped-form-checkbox>
        </div>
    </b-form-fieldset>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import BFormFieldset from "~/components/Form/BFormFieldset.vue";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const historyKeepDaysOptions = computed(() => {
    return [
        {
            value: 14,
            text: $gettext('Last 14 Days')
        },
        {
            value: 30,
            text: $gettext('Last 30 Days')
        },
        {
            value: 60,
            text: $gettext('Last 60 Days')
        },
        {
            value: 365,
            text: $gettext('Last Year')
        },
        {
            value: 730,
            text: $gettext('Last 2 Years')
        },
        {
            value: 0,
            text: $gettext('Indefinitely')
        },
    ]
});
</script>
