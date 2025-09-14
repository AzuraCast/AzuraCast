<template>
    <li
        class="page-item"
        :class="[
            (active) ? 'active' : '',
            (disabled) ? 'disabled' : ''
        ]"
    >
        <a
            v-if="!disabled"
            class="page-link"
            href="#"
            @click.prevent="onClick"
        >
            {{ label ?? page }}
        </a>
        <span
            v-else
            class="page-link"
        >
            {{ label ?? page }}
        </span>
    </li>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        page?: number,
        label?: string,
        active?: boolean,
        disabled?: boolean,
    }>(),
    {
        page: 1,
        active: false,
        disabled: false,
    }
);

const emit = defineEmits<{
    (e: 'click', page: number): void
}>();

const onClick = () => {
    emit('click', props.page);
}

</script>
