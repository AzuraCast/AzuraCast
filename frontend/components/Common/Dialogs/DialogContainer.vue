<template>
    <Teleport to="body">
        <dialog-row v-for="row in dialogs"
                    :key="row.id"
                    :id="row.id"
                    v-bind="row.options"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import DialogRow from "./DialogRow.vue";
import { useDialog } from "./useDialog.ts";

const dialogStore = useDialog();
const { dialogs } = storeToRefs(dialogStore);
</script>
