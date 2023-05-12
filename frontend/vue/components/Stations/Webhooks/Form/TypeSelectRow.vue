<template>
    <b-list-group-item
        href="#"
        class="px-3"
        @click.prevent="selectType()"
    >
        <h6 class="font-weight-bold mb-0">
            {{ typeInfo.title }}
        </h6>
        <p class="card-text small">
            {{ typeInfo.description }}
        </p>
    </b-list-group-item>
</template>

<script setup>

import {computed} from "vue";
import {get} from "lodash";

const props = defineProps({
    type: {
        type: String,
        required: true
    },
    types: {
        type: Object,
        required: true
    }
});

const typeInfo = computed(() => {
    return get(props.types, props.type);
});

const emit = defineEmits([
    'select'
]);

const selectType = () => {
    emit('select', props.type);
}
</script>
