<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Custom Branding') }}
    </h2>

    <section
        class="card mb-3"
        role="region"
        aria-labelledby="hdr_upload_custom_assets"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_upload_custom_assets"
                class="card-title"
            >
                {{ $gettext('Upload Custom Assets') }}
            </h2>
        </div>
        <div class="card-body">
            <ul class="list-unstyled">
                <custom-asset-form
                    id="asset_background"
                    class="mb-3"
                    :api-url="backgroundApiUrl"
                    :caption="$gettext('Public Page Background')"
                />
                <custom-asset-form
                    id="asset_album_art"
                    class="mb-3"
                    :api-url="albumArtApiUrl"
                    :caption="$gettext('Default Album Art')"
                />
                <custom-asset-form
                    id="asset_browser_icon"
                    :api-url="browserIconApiUrl"
                    :caption="$gettext('Browser Icon')"
                />
            </ul>
        </div>
    </section>

    <branding-form :api-url="settingsApiUrl" />
</template>

<script setup>
import CustomAssetForm from "./Branding/CustomAssetForm.vue";
import BrandingForm from "./Branding/BrandingForm.vue";

defineProps({
    settingsApiUrl: {
        type: String,
        required: true,
    },
    browserIconApiUrl: {
        type: String,
        required: true
    },
    backgroundApiUrl: {
        type: String,
        required: true
    },
    albumArtApiUrl: {
        type: String,
        required: true
    }
});
</script>

