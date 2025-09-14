<template>
    <slot name="default" />

    <span
        v-if="isRequired"
        class="text-danger"
    >
        <span aria-hidden="true">*</span>
        <span class="visually-hidden">{{ $gettext('Required') }}</span>
    </span>

    <span
        v-if="advanced"
        class="badge small text-bg-primary ms-2"
    >
        {{ $gettext('Advanced') }}
    </span>

    <span
        v-if="highCpu"
        class="badge small text-bg-warning ms-2"
        :title="$gettext('This setting can result in excessive CPU consumption and should be used with caution.')"
    >
        {{ $gettext('High CPU') }}
    </span>
</template>

<script setup lang="ts">
export type FormLabelParentProps = {
    advanced?: boolean,
    highCpu?: boolean
}

type FormLabelProps = FormLabelParentProps & {
    isRequired?: boolean
}

withDefaults(
    defineProps<FormLabelProps>(),
    {
        advanced: false,
        highCpu: false,
        isRequired: false
    }
);
</script>
