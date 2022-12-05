<template>
    <b-wrapped-form-group class="col-md-12" id="form_config_rate_limit" :field="form.config.rate_limit">
        <template #label="{lang}">
            <translate :key="lang">Only Post Once Every...</translate>
        </template>
        <template #default="props">
            <b-form-select :id="props.id" :options="rateLimitOptions" v-model="props.field.$model">
            </b-form-select>
        </template>
    </b-wrapped-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'CommonRateLimitFields',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langSeconds() {
            return this.$gettext('%{ seconds } seconds');
        },
        langMinutes() {
            return this.$gettext('%{ minutes } minutes');
        },
        rateLimitOptions() {
            return [
                {
                    text: this.$gettext('No Limit'),
                    value: 0,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 15}),
                    value: 15,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 30}),
                    value: 30,
                },
                {
                    text: this.$gettextInterpolate(this.langSeconds, {seconds: 60}),
                    value: 60,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 2}),
                    value: 120,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 5}),
                    value: 300,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 10}),
                    value: 600,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 15}),
                    value: 900,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 30}),
                    value: 1800,
                },
                {
                    text: this.$gettextInterpolate(this.langMinutes, {minutes: 60}),
                    value: 3600,
                }
            ];
        },
    }
}
</script>
