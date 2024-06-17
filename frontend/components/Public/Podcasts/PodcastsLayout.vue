<template>
    <minimal-layout>
        <full-height-card>
            <template #header>
                <div class="d-flex align-items-center">
                    <div class="flex-shrink">
                        <h2 class="card-title py-2">
                            <slot name="title">
                                {{ name }}
                            </slot>
                        </h2>
                    </div>
                    <div class="flex-fill text-end">
                        <inline-player ref="player" />
                    </div>
                </div>
            </template>

            <template #default>
                <router-view />
            </template>
        </full-height-card>
    </minimal-layout>
</template>
<script setup lang="ts">
import FullHeightCard from "~/components/Public/FullHeightCard.vue";
import InlinePlayer from "~/components/InlinePlayer.vue";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import MinimalLayout from "~/components/MinimalLayout.vue";
import {useProvidePodcastGroupLayout} from "~/components/Public/Podcasts/usePodcastGroupLayout.ts";

const props = defineProps({
    baseUrl: {
        type: String,
        required: true
    },
    groupLayout: {
        type: String,
        default: 'table'
    }
});

useProvidePodcastGroupLayout(props.groupLayout);

const {name} = useAzuraCastStation();
</script>
