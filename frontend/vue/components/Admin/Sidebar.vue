<template>
    <div class="navdrawer-header">
        <a
            class="navbar-brand px-0"
            :href="homeUrl"
        >
            {{ $gettext('Administration') }}
        </a>
    </div>

    <sidebar-menu
        :menu="menu"
        :active="active"
    />
</template>

<script setup>
import SidebarMenu from "~/components/Common/SidebarMenu.vue";

const props = defineProps({
    homeUrl: {
        type: String,
        required: true
    },
    menu: {
        type: Object,
        required: true
    },
    active: {
        type: String,
        default: null
    }
});
</script>
