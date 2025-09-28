<template>
    <div class="full-height-scrollable">
        <loading
            :loading="isLoading"
            lazy
        >
            <podcast
                v-if="data"
                :podcast="data"
            />
        </loading>
    </div>
</template>

<script setup lang="ts">
import Loading from "~/components/Common/Loading.vue";
import Podcast from "~/components/Public/Podcasts/Podcast.vue";
import {usePodcastQuery} from "~/components/Public/Podcasts/usePodcastQuery.ts";

const {data, isLoading} = usePodcastQuery();
</script>
