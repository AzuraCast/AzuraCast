<template>
    <template
        v-for="(row, index) in alt"
        :key="'chart_alt_'+index"
    >
        <p>{{ row.label }}</p>
        <dl>
            <template
                v-for="(valrow, valindex) in row.values"
                :key="'chart_alt_'+index+'_val_'+valindex"
            >
                <dt>
                    <template v-if="valrow.type === 'time'">
                        <time :data-original="valrow.original">{{ valrow.label }}</time>
                    </template>
                    <template v-else>
                        {{ valrow.label }}
                    </template>
                </dt>
                <dd>
                    {{ valrow.value }}
                </dd>
            </template>
        </dl>
    </template>
</template>

<script setup>
const props = defineProps({
    alt: {
        type: Array,
        default: () => {
            return [];
        }
    }
});
</script>
