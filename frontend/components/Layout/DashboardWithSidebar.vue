<template>
    <nav
        id="sidebar"
        class="navdrawer offcanvas offcanvas-start"
        tabindex="-1"
        :aria-label="$gettext('Sidebar')"
    >
        <slot name="sidebar"/>
    </nav>

    <div id="page-wrapper" class="has-sidebar">
        <main id="main">
            <div class="container" id="content">
                <slot/>
            </div>
        </main>

        <PanelFooter/>
    </div>
</template>

<script setup lang="ts">
import PanelFooter from "~/components/Common/PanelFooter.vue";
import {onMounted, onUnmounted} from "vue";

onMounted(() => {
    document.body.classList.add('has-sidebar');
});

onUnmounted(() => {
    document.body.classList.remove('has-sidebar');
});
</script>
