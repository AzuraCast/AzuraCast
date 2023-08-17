<template>
    <panel-layout v-bind="panelProps">
        <template #sidebar>
            <sidebar v-bind="sidebarProps" />
        </template>
        <template #default>
            <router-view />

            <header-inline-player />

            <lightbox ref="$lightbox" />
        </template>
    </panel-layout>
</template>

<script setup lang="ts">
import PanelLayout from "~/components/PanelLayout.vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import Sidebar from "~/components/Stations/Sidebar.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import HeaderInlinePlayer from "~/components/HeaderInlinePlayer.vue";
import {ref} from "vue";
import {useProvideLightbox} from "~/vendor/lightbox.ts";

const {panelProps, sidebarProps} = useAzuraCast();

const $lightbox = ref(); // Template Ref
useProvideLightbox($lightbox);
</script>
