<template>
    <div class="navdrawer-header">
        <router-link
            :to="{ name: 'admin:index'}"
            class="navbar-brand px-0"
        >
            {{ $gettext('Administration') }}
        </router-link>
    </div>

    <sidebar-menu :menu="menuItems" />
</template>

<script setup>
import SidebarMenu from "~/components/Common/SidebarMenuNew.vue";
import {useAdminMenu} from "~/components/Admin/menu";

const menuItems = useAdminMenu();
</script>
