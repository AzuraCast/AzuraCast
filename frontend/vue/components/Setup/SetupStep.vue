<template>
    <div class="stepper-horiz">
        <div class="stepper done">
            <div class="stepper-icon">
                <icon v-if="step > 1" icon="check"></icon>
                <span v-else>1</span>
            </div>
            <span class="stepper-text">
                <translate key="lang_step_register">Create Account</translate>
            </span>
        </div>
        <div class="stepper done">
            <div class="stepper-icon">
                <icon v-if="step > 2" icon="check"></icon>
                <span v-else>2</span>
            </div>
            <span class="stepper-text">
                <translate key="lang_step_station">Create Station</translate>
            </span>
        </div>
        <div class="stepper active">
            <div class="stepper-icon">
                <span>3</span>
            </div>
            <span class="stepper-text">
                <translate key="lang_step_settings">System Settings</translate>
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
    }
}
</script>
