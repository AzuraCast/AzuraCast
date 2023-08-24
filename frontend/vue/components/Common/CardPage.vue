<template>
    <section
        class="card"
        role="region"
        :aria-labelledby="headerId"
    >
        <div class="card-header text-bg-primary">
            <slot
                :id="headerId"
                name="header"
            >
                <h2
                    :id="headerId"
                    class="card-title"
                >
                    {{ title }}
                </h2>
            </slot>
        </div>
        <info-card v-if="slots.info">
            <slot name="info" />
        </info-card>
        <div
            v-if="slots.actions"
            class="card-body buttons"
        >
            <slot name="actions" />
        </div>

        <slot />

        <div
            v-if="slots.footer_actions"
            class="card-body buttons"
        >
            <slot name="footer_actions" />
        </div>
    </section>
</template>

<script setup lang="ts">
import InfoCard from "~/components/Common/InfoCard.vue";

const props = defineProps({
    title: {
        type: String,
        default: null
    },
    headerId: {
        type: String,
        default: "card_hdr"
    }
})

const slots = defineSlots();
</script>
