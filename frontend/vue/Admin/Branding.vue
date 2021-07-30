<template>
    <div>
        <h2 class="outside-card-header mb-1">
            <translate key="lang_header">Custom Branding</translate>
        </h2>

        <section class="card mb-3" role="region">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_custom_assets">Upload Custom Assets</translate>
                </h2>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <custom-asset-form id="asset_background" class="mb-3" :api-url="backgroundApiUrl"
                                       :caption="langBackground"></custom-asset-form>
                    <custom-asset-form id="asset_album_art" class="mb-3" :api-url="albumArtApiUrl"
                                       :caption="langAlbumArt"></custom-asset-form>
                    <custom-asset-form id="asset_browser_icon" :api-url="browserIconApiUrl"
                                       :caption="langBrowserIcon"></custom-asset-form>
                </ul>
            </div>
        </section>

        <branding-form :api-url="settingsApiUrl"></branding-form>
    </div>
</template>

<script>

import CustomAssetForm from "./Branding/CustomAssetForm";
import BrandingForm from "./Branding/BrandingForm";

export default {
    name: 'Branding',
    components: {BrandingForm, CustomAssetForm},
    props: {
        settingsApiUrl: String,
        browserIconApiUrl: String,
        backgroundApiUrl: String,
        albumArtApiUrl: String
    },
    computed: {
        langBrowserIcon() {
            return this.$gettext('Browser Icon');
        },
        langBackground() {
            return this.$gettext('Public Page Background');
        },
        langAlbumArt() {
            return this.$gettext('Default Album Art');
        }
    }
};
</script>
