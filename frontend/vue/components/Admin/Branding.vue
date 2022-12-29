<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Custom Branding') }}
        </h2>

        <section
            class="card mb-3"
            role="region"
        >
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
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
    </div>
</template>

<script setup>
import CustomAssetForm from "./Branding/CustomAssetForm.vue";
import BrandingForm from "./Branding/BrandingForm.vue";

const props = defineProps({
    settingsApiUrl: String,
    browserIconApiUrl: String,
    backgroundApiUrl: String,
    albumArtApiUrl: String
});
</script>

