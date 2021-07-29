<template>
    <div>
        <h2 class="outside-card-header mb-1">
            <translate key="lang_header">Custom Branding</translate>
        </h2>

        <div class="card-columns mb-3">
            <section class="card" role="region">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">{{ langBackground }}</h2>
                </div>
                <div class="card-body">
                    <custom-asset-form :api-url="backgroundApiUrl" :caption="langBackground"></custom-asset-form>
                </div>
            </section>
            <section class="card" role="region">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">{{ langAlbumArt }}</h2>
                </div>
                <div class="card-body">
                    <custom-asset-form :api-url="albumArtApiUrl" :caption="langAlbumArt"></custom-asset-form>
                </div>
            </section>
            <section class="card" role="region">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">{{ langBrowserIcon }}</h2>
                </div>
                <div class="card-body">
                    <custom-asset-form :api-url="browserIconApiUrl" :caption="langBrowserIcon"></custom-asset-form>
                </div>
            </section>
        </div>

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
