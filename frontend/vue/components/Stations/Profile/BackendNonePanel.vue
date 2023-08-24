<template>
    <card-page
        id="profile-backend"
        header-id="hdr_backend_disabled"
        :title="$gettext('AutoDJ Disabled')"
    >
        <div class="card-body">
            <p class="card-text">
                {{
                    $gettext('AutoDJ has been disabled for this station. No music will automatically be played when a source is not live.')
                }}
            </p>
        </div>
    </card-page>
</template>

<script setup lang="ts">
import CardPage from "~/components/Common/CardPage.vue";
</script>
