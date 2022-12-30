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

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'CommonRateLimitFields',
    components: {BWrappedFormGroup},
    props: {
        form: {
            type: Object,
            required: true
        }
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
