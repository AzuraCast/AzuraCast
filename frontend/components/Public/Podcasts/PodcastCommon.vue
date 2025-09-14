<template>
    <h4 class="card-title mb-1">
        {{ podcast.title }}
        <span v-if="podcast.author">
            <br>
            <small>
                {{ $gettext('by') }} <a
                    :href="'mailto:'+podcast.email"
                    target="_blank"
                >{{ podcast.author }}</a>
            </small>
        </span>
    </h4>

    <div class="badges my-2">
        <span class="badge text-bg-info">
            {{ podcast.language_name }}
        </span>
        <span
            v-for="category in podcast.categories"
            :key="category.category"
            class="badge text-bg-secondary"
        >
            {{ category.text }}
        </span>
    </div>
    <p class="card-text">
        {{ podcast.description }}
    </p>
    <p
        v-if="podcast.branding_config.public_custom_html"
        class="card-text"
        v-html="podcast.branding_config.public_custom_html"
    />
</template>

<script setup lang="ts">
import { ApiPodcastRow } from "./usePodcastQuery";

defineProps<{
    podcast: ApiPodcastRow
}>();
</script>
