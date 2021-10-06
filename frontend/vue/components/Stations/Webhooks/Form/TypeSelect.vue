<template>
    <b-form-group>
        <template #label>
            <translate key="lang_select_type_header">Select Web Hook Type</translate>
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

<script>
export default {
    name: 'TypeSelect',
    emits: ['select'],
    props: {
        webhookTypes: Object,
    },
    methods: {
        selectType(type) {
            this.$emit('select', type);
        }
    }
}
</script>
