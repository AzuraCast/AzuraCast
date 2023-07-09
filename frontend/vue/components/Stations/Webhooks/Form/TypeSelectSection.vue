<template>
    <section class="card mb-3">
        <div class="card-header text-bg-primary">
            <h3 class="card-subtitle">
                {{ title }}
            </h3>
        </div>

        <div class="list-group list-group-flush">
            <a
                v-for="type in types"
                :key="type.key"
                class="list-group-item list-group-item-action"
                href="#"
                @click.prevent="selectType(type.key)"
            >
                <h6 class="font-weight-bold mb-0">
                    {{ type.title }}
                </h6>
                <p class="card-text small">
                    {{ type.description }}
                </p>
            </a>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    title: {
        type: String,
        required: true
    },
    types: {
        type: Array,
        required: true
    }
});

const emit = defineEmits([
    'select'
]);

const selectType = (type) => {
    emit('select', type);
}
</script>
