<template>
    <div class="offcanvas-body">
        <sidebar-menu :menu="menuItems" />
    </div>
</template>

<script setup lang="ts">
import SidebarMenu from "~/components/Common/SidebarMenu.vue";
import {useStationsMenu} from "~/components/Stations/menu";

const menuItems = useStationsMenu();
</script>
