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

import {get} from "lodash";

const props = defineProps({
    fields: {
        type: Object,
        required: true
    },
    data: {
        type: Array,
        required: true
    }
});
</script>
