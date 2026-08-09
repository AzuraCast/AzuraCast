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

<script setup lang="ts">
import { useTemplateRef } from "vue";
import BrandingForm from "~/components/Admin/Branding/BrandingForm.vue";
import CustomAssetForm from "~/components/Admin/Branding/CustomAssetForm.vue";
import CardPage from "~/components/Common/CardPage.vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import { useApiRouter } from "~/functions/useApiRouter.ts";
import { useProvideLightbox } from "~/vendor/lightbox";

const { getApiUrl } = useApiRouter();
const settingsApiUrl = getApiUrl("/admin/settings/branding");
const browserIconApiUrl = getApiUrl("/admin/custom_assets/browser_icon");
const backgroundApiUrl = getApiUrl("/admin/custom_assets/background");
const albumArtApiUrl = getApiUrl("/admin/custom_assets/album_art");

const $lightbox = useTemplateRef("$lightbox");
useProvideLightbox($lightbox);
</script>

