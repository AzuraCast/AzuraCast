<template>
    <dashboard-with-sidebar>
        <template #sidebar>
            <sidebar/>
        </template>
        <template #default>
            <router-view/>
        </template>
    </dashboard-with-sidebar>
</template>
<script setup lang="ts">
import DashboardWithSidebar from "~/components/Layout/DashboardWithSidebar.vue";
import Sidebar from "~/components/Admin/Sidebar.vue";
</script>
