<template>
    <div id="page-wrapper">
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
</script>
