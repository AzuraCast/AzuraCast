<template>
    <panel-layout v-bind="panelProps">
        <template #sidebar>
            <sidebar/>
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
import {useAzuraCastPanelProps} from "~/vendor/azuracast.ts";
import Sidebar from "~/components/Stations/Sidebar.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import HeaderInlinePlayer from "~/components/HeaderInlinePlayer.vue";
import {useTemplateRef} from "vue";
import {useProvideLightbox} from "~/vendor/lightbox.ts";

const panelProps = useAzuraCastPanelProps();

const $lightbox = useTemplateRef('$lightbox');

useProvideLightbox($lightbox);
</script>
