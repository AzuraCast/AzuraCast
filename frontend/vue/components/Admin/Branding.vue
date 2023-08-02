<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Custom Branding') }}
    </h2>

    <card-page
        class="mb-3"
        header-id="hdr_upload_custom_assets"
        :title="$gettext('Upload Custom Assets')"
    >
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
    </card-page>

    <branding-form :api-url="settingsApiUrl" />

    <lightbox ref="$lightbox" />
</template>

<script setup>
import CustomAssetForm from "./Branding/CustomAssetForm.vue";
import BrandingForm from "./Branding/BrandingForm.vue";
import CardPage from "~/components/Common/CardPage.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import {ref} from "vue";
import {useProvideLightbox} from "~/vendor/lightbox";

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

const $lightbox = ref(); // Template Ref
useProvideLightbox($lightbox);
</script>

