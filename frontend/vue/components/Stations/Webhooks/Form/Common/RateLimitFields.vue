<template>
    <form-group-select
        id="form_config_rate_limit"
        class="col-md-12"
        :field="form.config.rate_limit"
        :options="rateLimitOptions"
        :label="$gettext('Only Post Once Every...')"
    />
</template>

<script setup>
import {useTranslate} from "~/vendor/gettext";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext, interpolate} = useTranslate();

const langSeconds = $gettext('%{ seconds } seconds');
const langMinutes = $gettext('%{ minutes } minutes');

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
    }
];
</script>
