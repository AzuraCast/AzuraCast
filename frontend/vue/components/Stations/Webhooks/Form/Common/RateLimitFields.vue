<template>
    <b-wrapped-form-group
        id="form_config_rate_limit"
        class="col-md-12"
        :field="form.config.rate_limit"
    >
        <template #label>
            {{ $gettext('Only Post Once Every...') }}
        </template>
        <template #default="slotProps">
            <b-form-select
                :id="slotProps.id"
                v-model="slotProps.field.$model"
                :options="rateLimitOptions"
            />
        </template>
    </b-wrapped-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext, $gettextInterpolate} = useTranslate();

const langSeconds = $gettext('%{ seconds } seconds');
const langMinutes = $gettext('%{ minutes } minutes');

const rateLimitOptions = [
    {
        text: $gettext('No Limit'),
        value: 0,
    },
    {
        text: $gettextInterpolate(langSeconds, {seconds: 15}),
        value: 15,
    },
    {
        text: $gettextInterpolate(langSeconds, {seconds: 30}),
        value: 30,
    },
    {
        text: this.$gettextInterpolate(this.langSeconds, {seconds: 60}),
        value: 60,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 2}),
        value: 120,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 5}),
        value: 300,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 10}),
        value: 600,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 15}),
        value: 900,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 30}),
        value: 1800,
    },
    {
        text: $gettextInterpolate(langMinutes, {minutes: 60}),
        value: 3600,
    }
];
</script>
