<template>
    <div class="row-of-cards">
        <section
            class="card"
            role="region"
            aria-labelledby="hdr_custom_assets"
        >
            <div class="card-header text-bg-primary">
                <h2
                    id="hdr_custom_assets"
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
                </ul>
            </div>
        </section>

        <branding-form :profile-edit-url="profileEditUrl" />
    </div>
</template>

<script setup lang="ts">
import BrandingForm from "~/components/Stations/Branding/BrandingForm.vue";
import CustomAssetForm from "~/components/Admin/Branding/CustomAssetForm.vue";
import {getStationApiUrl} from "~/router";

const profileEditUrl = getStationApiUrl('/profile/edit');
const backgroundApiUrl = getStationApiUrl('/custom_assets/background');
const albumArtApiUrl = getStationApiUrl('/custom_assets/album_art');
</script>

