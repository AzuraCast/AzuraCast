<template>
    <Teleport to="body">
        <Dialog v-for="row in dialogs"
                :key="row.id"
                :id="row.id"
                v-bind="row.options"
        />
    </Teleport>
</template>

<script setup lang="ts">
import {useDialog} from "~/functions/useDialog.ts";
import Dialog from "~/components/Common/Dialog.vue";
import {storeToRefs} from "pinia";

const dialogStore = useDialog();
const {dialogs} = storeToRefs(dialogStore);
</script>
