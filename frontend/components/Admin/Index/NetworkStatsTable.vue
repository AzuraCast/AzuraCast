<template>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th
                        v-for="key in fields"
                        :key="`${key}-header`"
                    >
                        {{ key }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, index) in data"
                    :key="index"
                >
                    <td
                        v-for="key in fields"
                        :key="`row-${index}-${key}`"
                    >
                        {{ get(row, key, null) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import {get, isObject, mapValues} from "lodash";
import {computed} from "vue";

const props = defineProps<{
    row: object,
}>();

const fields = computed<string[]>(() => {
    return Object.keys(props.row);
});

interface HasReadable {
    readable: string
}

const data = computed(() => {
    const items = mapValues(
        props.row,
        (value: HasReadable | string) => {
            return (isObject(value) && value.readable)
                ? value.readable + '/s'
                : value;
        }
    );

    return [items];
});
</script>
