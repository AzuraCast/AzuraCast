<template>
    <div class="navdrawer-header">
        <router-link
            :to="{ name: 'admin:index'}"
            class="navbar-brand px-0"
        >
            {{ $gettext('Administration') }}
        </router-link>
    </div>

    <div class="offcanvas-body">
        <sidebar-menu :menu="menuItems" />
    </div>
</template>

<script setup lang="ts">
import { useAdminMenu } from "~/components/Admin/menu";
import SidebarMenu from "~/components/Common/SidebarMenu.vue";

const menuItems = useAdminMenu();
</script>
