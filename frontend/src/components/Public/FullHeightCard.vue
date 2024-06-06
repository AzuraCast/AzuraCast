<template>
    <section
        id="content"
        class="full-height-wrapper"
        role="main"
    >
        <div class="card">
            <div class="card-header text-bg-primary">
                <slot name="header">
                    <h2 class="card-title py-2">
                        <slot name="title" />
                    </h2>
                </slot>
            </div>

            <slot name="default" />
        </div>
    </section>

    <lightbox ref="$lightbox" />
</template>

<script setup lang="ts">
import Lightbox from "~/components/Common/Lightbox.vue";
import {ref} from "vue";
import {LightboxTemplateRef, useProvideLightbox} from "~/vendor/lightbox.ts";

const $lightbox = ref<LightboxTemplateRef>(null);
useProvideLightbox($lightbox);
</script>
