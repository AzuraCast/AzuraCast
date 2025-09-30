<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('Web Hook Name')"
                :description="$gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')"
            />
        </div>
        <div class="row g-3">
            <form-group-multi-check
                v-if="triggersForType.length > 0"
                id="edit_form_triggers"
                class="col-md-7"
                :field="r$.triggers"
                :options="triggerOptions"
                stacked
                :label="$gettext('Web Hook Triggers')"
                :description="$gettext('Select the event(s) that will trigger this webhook. If no events are selected, the webhook will run for all applicable events.')"
            />
            
            <form-group-select
                id="form_config_rate_limit"
                class="col-md-5"
                :field="r$.config.rate_limit"
                :options="rateLimitOptions"
                :label="$gettext('Only Trigger Once Every...')"
                :description="$gettext('Use this setting to limit the rate of web hooks sent by the system. This can be useful to avoid rate limits on third party services.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {map, pick} from "es-toolkit/compat";
import Tab from "~/components/Common/Tab.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {getTriggers, WebhookTriggerDetails} from "~/entities/Webhooks.ts";
import {computed} from "vue";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useStationsWebhooksForm} from "~/components/Stations/Webhooks/Form/form.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {storeToRefs} from "pinia";

const props = defineProps<{
    triggerDetails: WebhookTriggerDetails
}>();

const {
    form
} = storeToRefs(useStationsWebhooksForm());

const {r$} = useAppScopedRegle(
    form,
    {
        name: {required}
    },
    {
        namespace: 'station-webhooks'
    }
);

const tabClass = useFormTabClass(r$);

const triggersForType = computed(() => {
    return (form.value.type) ? getTriggers(form.value.type) : [];
});

const triggerOptions = computed(() => {
    const triggerDetailsForType = pick(props.triggerDetails, ...triggersForType.value);

    return map(
        triggerDetailsForType,
        (trigger, key) => {
            return {
                value: key,
                text: trigger.title,
                description: trigger.description
            };
        }
    )
});

const {$gettext, interpolate} = useTranslate();

const langSeconds = $gettext('%{seconds} seconds');
const langMinutes = $gettext('%{minutes} minutes');
const langHours = $gettext('%{hours} hours');

const rateLimitOptions = [
    {
        text: $gettext('No Limit'),
        value: 0,
    },
    {
        text: interpolate(langSeconds, {seconds: 15}),
        value: 15,
    },
    {
        text: interpolate(langSeconds, {seconds: 30}),
        value: 30,
    },
    {
        text: interpolate(langSeconds, {seconds: 60}),
        value: 60,
    },
    {
        text: interpolate(langMinutes, {minutes: 2}),
        value: 120,
    },
    {
        text: interpolate(langMinutes, {minutes: 5}),
        value: 300,
    },
    {
        text: interpolate(langMinutes, {minutes: 10}),
        value: 600,
    },
    {
        text: interpolate(langMinutes, {minutes: 15}),
        value: 900,
    },
    {
        text: interpolate(langMinutes, {minutes: 30}),
        value: 1800,
    },
    {
        text: interpolate(langMinutes, {minutes: 60}),
        value: 3600,
    },
    {
        text: interpolate(langHours, {hours: 2}),
        value: 7200,
    },
    {
        text: interpolate(langHours, {hours: 3}),
        value: 10800,
    },
    {
        text: interpolate(langHours, {hours: 6}),
        value: 21600,
    },
    {
        text: interpolate(langHours, {hours: 12}),
        value: 43200,
    }
];
</script>
