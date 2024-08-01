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
const props = defineProps({
    page: {
        type: Number,
        default: 1
    },
    label: {
        type: String,
        default: null
    },
    active: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click']);

const onClick = () => {
    emit('click', props.page);
}

</script>
