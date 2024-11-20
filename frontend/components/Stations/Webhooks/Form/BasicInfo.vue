<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Web Hook Name')"
                :description="$gettext('Choose a name for this webhook that will help you distinguish it from others. This will only be shown on the administration page.')"
            />
        </div>
        <div class="row g-3">
            <form-group-multi-check
                v-if="triggers.length > 0"
                id="edit_form_triggers"
                class="col-md-7"
                :field="v$.triggers"
                :options="triggerOptions"
                stacked
                :label="$gettext('Web Hook Triggers')"
                :description="$gettext('This web hook will only run when the selected event(s) occur on this specific station.')"
            />

            <form-group-select
                id="form_config_rate_limit"
                class="col-md-5"
                :field="v$.config.rate_limit"
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
import {map} from "lodash";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useTranslate} from "~/vendor/gettext.ts";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    triggers: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        name: {required},
        triggers: {},
        config: {
            rate_limit: {}
        }
    },
    form,
    {
        name: null,
        triggers: [],
        config: {
            rate_limit: 0
        }
    }
);

const triggerOptions = map(
    props.triggers,
    (trigger) => {
        return {
            value: trigger.key,
            text: trigger.title,
            description: trigger.description
        };
    }
);

const {$gettext, interpolate} = useTranslate();

const langSeconds = $gettext('%{ seconds } seconds');
const langMinutes = $gettext('%{ minutes } minutes');
const langHours = $gettext('%{ hours } hours');

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
