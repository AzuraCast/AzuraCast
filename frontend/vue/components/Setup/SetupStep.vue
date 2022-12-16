<template>
    <div class="stepper-horiz">
        <div :class="getStepperClass(1)">
            <div class="stepper-icon">
                <icon v-if="step > 1" icon="check"></icon>
                <span v-else>1</span>
            </div>
            <span class="stepper-text">
                {{ $gettext('Create Account') }}
            </span>
        </div>
        <div :class="getStepperClass(2)">
            <div class="stepper-icon">
                <icon v-if="step > 2" icon="check"></icon>
                <span v-else>2</span>
            </div>
            <span class="stepper-text">
                {{ $gettext('Create Station') }}
            </span>
        </div>
        <div :class="getStepperClass(3)">
            <div class="stepper-icon">
                <span>3</span>
            </div>
            <span class="stepper-text">
                {{ $gettext('System Settings') }}
            </span>
        </div>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";

export default {
    name: 'SetupStep',
    components: {Icon},
    props: {
        step: Number
    },
    methods: {
        getStepperClass(currentStep) {
            if (this.step === currentStep) {
                return ['stepper', 'active'];
            } else if (this.step > currentStep) {
                return ['stepper', 'done'];
            } else {
                return ['stepper'];
            }
        }
    }
}
</script>
