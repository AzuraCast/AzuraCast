<template>
    <dashboard-wrapper>
        <dashboard-no-sidebar>
            <slot/>
        </dashboard-no-sidebar>
    </dashboard-wrapper>
</template>

<script setup lang="ts">
import DashboardWrapper from "~/components/Layout/DashboardLayout.vue";
import DashboardNoSidebar from "~/components/Layout/DashboardNoSidebar.vue";
</script>
