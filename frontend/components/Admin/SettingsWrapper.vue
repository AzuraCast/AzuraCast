<template>
    <settings :loading="loading" @saved="onSaved"/>
</template>

<script setup lang="ts">
import Settings from "~/components/Admin/Settings.vue";
import {delay} from "es-toolkit";
import {ref} from "vue";

const loading = ref(false);

const onSaved = async () => {
    loading.value = true;

    await delay(2000);

    window.location.reload();
}
</script>
