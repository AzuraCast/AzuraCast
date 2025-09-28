<template>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <toast
            v-for="toast in toasts"
            :key="toast.id"
            v-bind="toast"
        >
            <template #default v-if="toast.slot">
                <component :is="() => toast.slot"/>
            </template>
        </toast>
    </div>
</template>

<script setup lang="ts">
import {useNotify} from "./useNotify.ts";
import {storeToRefs} from "pinia";
import Toast from "./Toast.vue";

const notifyStore = useNotify();
const {toasts} = storeToRefs(notifyStore);
</script>
