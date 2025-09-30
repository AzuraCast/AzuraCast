<template>
    <div class="d-flex justify-content-center align-items-center text-center mb-4 px-5">
        <div
            class="rounded border p-2 m-2"
            :class="getStepperClass(1)"
        >
            <small>{{ $gettext('Step %{step}', {step: 1}) }}</small><br>
            {{ $gettext('Create Account') }}
        </div>
        <div>
            <icon-ic-arrow-right class="xl"/>
        </div>
        <div
            class="rounded border p-2 m-2"
            :class="getStepperClass(2)"
        >
            <small>{{ $gettext('Step %{step}', {step: 2}) }}</small><br>
            {{ $gettext('Create Station') }}
        </div>
        <div>
            <icon-ic-arrow-right class="xl"/>
        </div>
        <div
            class="rounded border p-2 m-2"
            :class="getStepperClass(3)"
        >
            <small>{{ $gettext('Step %{step}', {step: 3}) }}</small><br>
            {{ $gettext('System Settings') }}
        </div>
    </div>
</template>

<script setup lang="ts">
import IconIcArrowRight from "~icons/ic/baseline-arrow-right";

const props = withDefaults(
    defineProps<{
        step: number
    }>(),
    {
        step: 1
    }
);

const getStepperClass = (currentStep: number) => {
    if (props.step === currentStep) {
        return ['text-primary-emphasis', 'bg-primary-subtle', 'border-primary-subtle'];
    } else {
        return ['text-secondary-emphasis', 'bg-secondary-subtle', 'border-secondary-subtle'];
    }
}
</script>
