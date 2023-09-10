<template>
    <form-group-select
        id="form_config_rate_limit"
        class="col-md-12"
        :field="v$.config.rate_limit"
        :options="rateLimitOptions"
        :label="$gettext('Only Post Once Every...')"
    />
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$} = useVuelidateOnFormTab(
    {
        config: {
            rate_limit: {},
        }
    },
    form,
    {
        config: {
            rate_limit: 0
        }
    }
);

const {$gettext, interpolate} = useTranslate();

const langSeconds = $gettext('%{ seconds } seconds');
const langMinutes = $gettext('%{ minutes } minutes');
const langHours   = $gettext('%{ hours } hours');

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
