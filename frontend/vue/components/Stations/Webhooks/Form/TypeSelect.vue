<template>
    <b-form-group>
        <template #label>
            {{ $gettext('Select Web Hook Type') }}
        </template>

        <b-list-group>
            <b-list-group-item v-for="(info, key) in webhookTypes" :key="key" href="#" @click.prevent="selectType(key)"
                               class="px-3">
                <h6 class="font-weight-bold mb-0">{{ info.name }}</h6>
                <p class="card-text small">{{ info.description }}</p>
            </b-list-group-item>
        </b-list-group>
    </b-form-group>
</template>

<script setup>
const props = defineProps({
    webhookTypes: Object,
});

const emit = defineEmits(['select']);

const selectType = (type) => {
    emit('select', type);
}
</script>
