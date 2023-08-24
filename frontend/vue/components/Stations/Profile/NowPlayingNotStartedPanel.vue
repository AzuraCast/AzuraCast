<template>
    <card-page
        id="profile-now-playing"
        header-id="hdr_now_playing"
        :title="$gettext('On the Air')"
    >
        <div class="card-body">
            <p class="card-text">
                {{
                    $gettext('Information about the current playing track will appear here once your station has started.')
                }}
            </p>
        </div>
    </card-page>
</template>

<script setup lang="ts">
import CardPage from "~/components/Common/CardPage.vue";
</script>
