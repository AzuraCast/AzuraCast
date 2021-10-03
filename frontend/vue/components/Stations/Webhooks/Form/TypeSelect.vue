<template>
    <div>
        <h2>
            <translate key="lang_select_type_header">Select Web Hook Type</translate>
        </h2>

        <b-list-group>
            <b-list-group-item v-for="(info, key) in webhookTypes" :key="key" @click.prevent="selectType(key)"
                               class="flex-column align-items-start">
                <h5 class="mb-1">{{ info.name }}</h5>
                <p>{{ info.description }}</p>
            </b-list-group-item>
        </b-list-group>
    </div>
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
