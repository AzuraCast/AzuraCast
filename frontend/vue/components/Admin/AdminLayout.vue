<template>
    <panel-layout v-bind="panelProps">
        <template
            v-if="!isHome"
            #sidebar
        >
            <sidebar v-bind="sidebarProps" />
        </template>
        <template #default>
            <router-view />
        </template>
    </panel-layout>
</template>

<script setup lang="ts">
import PanelLayout from "~/components/PanelLayout.vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import {useRoute} from "vue-router";
import {ref, watch} from "vue";
import Sidebar from "~/components/Admin/Sidebar.vue";

const {panelProps, sidebarProps} = useAzuraCast();

const isHome = ref(true);
const route = useRoute();

watch(route, (newRoute) => {
    isHome.value = newRoute.name === 'admin:index';
});
</script>
