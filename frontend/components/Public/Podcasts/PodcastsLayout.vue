<template>
    <minimal-layout>
        <full-height-card>
            <template #header>
                <div class="d-flex align-items-center">
                    <div class="flex-shrink">
                        <h2 class="card-title py-2">
                            <slot name="title">
                                {{ stationName }}
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
import MinimalLayout from "~/components/MinimalLayout.vue";
import {useProvidePodcastGroupLayout} from "~/components/Public/Podcasts/usePodcastGroupLayout.ts";

const props = withDefaults(
    defineProps<{
        stationName: string | null,
        baseUrl: string,
        groupLayout?: string,
    }>(),
    {
        groupLayout: 'table'
    }
);

useProvidePodcastGroupLayout(props.groupLayout);
</script>
