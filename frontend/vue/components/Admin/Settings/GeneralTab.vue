<template>
    <tab
        :label="$gettext('Settings')"
        :item-header-class="tabClass"
    >
        <form-fieldset>
            <div class="row g-3">
                <form-group-field
                    id="edit_form_base_url"
                    class="col-md-6"
                    :field="v$.base_url"
                    input-type="url"
                    :label="$gettext('Site Base URL')"
                    :description="$gettext('The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.')"
                />

                <form-group-field
                    id="edit_form_instance_name"
                    class="col-md-6"
                    :field="v$.instance_name"
                    :label="$gettext('AzuraCast Instance Name')"
                    :description="$gettext('This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.')"
                />

                <form-group-checkbox
                    id="edit_form_prefer_browser_url"
                    class="col-md-6"
                    :field="v$.prefer_browser_url"
                    :label="$gettext('Prefer Browser URL (If Available)')"
                >
                    <template #description>
                        {{
                            $gettext('If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to "No" to always use the base URL.')
                        }}
                    </template>
                </form-group-checkbox>

                <form-group-checkbox
                    id="edit_form_use_radio_proxy"
                    class="col-md-6"
                    :field="v$.use_radio_proxy"
                    :label="$gettext('Use Web Proxy for Radio')"
                    :description="$gettext('By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports (80 and 443).')"
                />

                <form-group-multi-check
                    id="edit_form_history_keep_days"
                    class="col-md-6"
                    :field="v$.history_keep_days"
                    :options="historyKeepDaysOptions"
                    stacked
                    radio
                    :label="$gettext('Days of Playback History to Keep')"
                    :description="$gettext('Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space.')"
                />

                <form-group-checkbox
                    id="edit_form_enable_static_nowplaying"
                    class="col-md-6"
                    :field="v$.enable_static_nowplaying"
                    :label="$gettext('Use High-Performance Now Playing Updates')"
                    :description="$gettext('Uses either Websockets, Server-Sent Events (SSE) or static JSON files to serve Now Playing data on public pages. This improves performance, especially with large listener volume. Disable this if you are encountering problems with the service or use multiple URLs to serve your public pages.')"
                />

                <form-group-checkbox
                    id="edit_form_enable_advanced_features"
                    class="col-md-6"
                    :field="v$.enable_advanced_features"
                    :label="$gettext('Enable Advanced Features')"
                    :description="$gettext('Enable certain advanced features in the web interface, including advanced playlist configuration, station port assignment, changing base media directories and other functionality that should only be used by users who are comfortable with advanced functionality.')"
                />
            </div>
        </form-fieldset>
    </tab>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormFieldset from "~/components/Form/FormFieldset";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        base_url: {required},
        instance_name: {},
        prefer_browser_url: {},
        use_radio_proxy: {},
        history_keep_days: {required},
        enable_static_nowplaying: {},
        enable_advanced_features: {},
    },
    form,
    {
        base_url: '',
        instance_name: '',
        prefer_browser_url: true,
        use_radio_proxy: true,
        history_keep_days: 7,
        enable_static_nowplaying: true,
        enable_advanced_features: true,
    }
);

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
