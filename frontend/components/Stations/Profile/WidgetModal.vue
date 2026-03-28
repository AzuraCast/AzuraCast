<template>
    <modal
        id="embed_modal"
        ref="modalRef"
        size="xl"
        :title="$gettext('Player Embed Widget Builder')"
        hide-footer
        no-enforce-focus
    >
        <div class="row g-4">
            <div class="col-md-8">
                <section
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <h2 class="card-title">
                                    {{ $gettext('Widget Builder') }}
                                </h2>
                            </div>
                            <div class="flex-shrink-0">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-dark"
                                    @click="resetStore"
                                >
                                    {{ $gettext('Reset All To Defaults') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3 align-items-stretch">
                            <div class="col-md-6">
                                <form-group-multi-check
                                    id="embed_type"
                                    v-model="selectedType"
                                    :label="$gettext('Widget Type')"
                                    :options="types"
                                    stacked
                                    radio
                                />
                            </div>
                            <div class="col-md-6">
                                <form-group-multi-check
                                    id="embed_theme"
                                    v-model="selectedTheme"
                                    :label="$gettext('Theme')"
                                    name="embed_theme"
                                    :options="themes"
                                    stacked
                                    radio
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="selectedType === 'player'"
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Player Customization') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <tabs content-class="mt-3">
                            <tab :label="$gettext('Appearance')">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_primary_color"
                                            v-model="customization.primaryColor"
                                            :label="$gettext('Primary Color')"
                                        >
                                            <template #default="{id, model, fieldClass}">
                                                <input
                                                    :id="id"
                                                    v-model="model.$model"
                                                    type="color"
                                                    :class="fieldClass"
                                                    class="form-control form-control-color"
                                                />
                                            </template>
                                        </form-group-field>
                                    </div>
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_background_color"
                                            v-model="customization.backgroundColor"
                                            :label="$gettext('Background Color')"
                                        >
                                            <template #default="{id, model, fieldClass}">
                                                <input
                                                    :id="id"
                                                    v-model="model.$model"
                                                    type="color"
                                                    :class="fieldClass"
                                                    class="form-control form-control-color"
                                                />
                                            </template>
                                        </form-group-field>
                                    </div>
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_text_color"
                                            v-model="customization.textColor"
                                            :label="$gettext('Text Color')"
                                        >
                                            <template #default="{id, model, fieldClass}">
                                                <input
                                                    :id="id"
                                                    v-model="model.$model"
                                                    type="color"
                                                    :class="fieldClass"
                                                    class="form-control form-control-color"
                                                />
                                            </template>
                                        </form-group-field>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <form-group-checkbox
                                            id="embed_show_album_art"
                                            v-model="customization.showAlbumArt"
                                            :label="$gettext('Show Album Art')"
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <form-group-checkbox
                                            id="embed_rounded_corners"
                                            v-model="customization.roundedCorners"
                                            :label="$gettext('Rounded Corners')"
                                        />
                                    </div>
                                </div>
                            </tab>
                            <tab :label="$gettext('Functionality')">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_initial_volume"
                                            v-model="customization.initialVolume"
                                            input-number
                                            :label="$gettext('Initial Volume')"
                                        >
                                            <template #default="{id, model, fieldClass}">
                                                <div class="input-group">
                                                    <input
                                                        :id="id"
                                                        v-model="model.$model"
                                                        :class="fieldClass"
                                                        type="range"
                                                        class="form-range"
                                                        min="0"
                                                        max="100"
                                                        step="1"
                                                    />
                                                    <span class="input-group-text">
                                                        {{ model.$model }}%
                                                    </span>
                                                </div>
                                            </template>
                                        </form-group-field>
                                    </div>
                                    <div class="col-md-8">
                                        <form-group-checkbox
                                            id="embed_autoplay"
                                            v-model="customization.autoplay"
                                            :label="$gettext('Autoplay')"
                                        />
                                        <form-group-checkbox
                                            id="embed_show_volume_controls"
                                            v-model="customization.showVolumeControls"
                                            :label="$gettext('Show Volume Controls')"
                                        />
                                        <form-group-checkbox
                                            id="embed_show_track_progress"
                                            v-model="customization.showTrackProgress"
                                            :label="$gettext('Show Track Progress')"
                                        />
                                        <form-group-checkbox
                                            id="embed_show_stream_selection"
                                            v-model="customization.showStreamSelection"
                                            :label="$gettext('Show Stream Selection')"
                                        />
                                    </div>
                                </div>
                            </tab>
                            <tab :label="$gettext('Layout')">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <form-group-multi-check
                                            id="embed_layout"
                                            v-model="customization.layout"
                                            :label="$gettext('Layout Style')"
                                            :options="layoutOptions"
                                            stacked
                                            radio
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <form-group-field
                                            id="embed_width"
                                            v-model="customWidth"
                                            :label="$gettext('Width (px or %)')"
                                            :input-attrs="{
                                                placeholder: '100%'
                                            }"
                                        />
                                        <form-group-field
                                            id="embed_height"
                                            v-model.number="customHeight"
                                            :label="$gettext('Height (px)')"
                                            :input-attrs="{
                                                min: 100,
                                                placeholder: '150'
                                            }"
                                            input-number
                                        />
                                    </div>
                                </div>
                            </tab>
                            <tab :label="$gettext('Advanced')">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <form-group-checkbox
                                            id="embed_popup_player"
                                            v-model="customization.enablePopupPlayer"
                                            :label="$gettext('Enable Popup Player')"
                                        />

                                        <form-group-checkbox
                                            id="embed_continuous_play"
                                            v-model="customization.continuousPlay"
                                            :label="$gettext('Continuous Play Across Pages')"
                                        />
                                    </div>
                                    <div class="col-md-7">
                                        <form-group-field
                                            id="embed_custom_css"
                                            v-model="customization.customCss"
                                            :label="$gettext('Custom CSS')"
                                            :description="$gettext('Additional CSS to customize the widget appearance')"
                                        >
                                            <template #default="{id, model, fieldClass}">
                                                <textarea
                                                    :id="id"
                                                    v-model="model.$model"
                                                    :class="fieldClass"
                                                    rows="4"
                                                    class="form-control font-monospace"
                                                    placeholder="// .radio-player-widget { border-radius: 10px; }"
                                                />
                                            </template>
                                        </form-group-field>
                                    </div>
                                </div>
                            </tab>
                        </tabs>
                    </div>
                </section>
            </div>
            <div class="col-md-4">
                <section
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Embed Code') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <textarea
                            class="full-width form-control text-preformatted mb-2"
                            spellcheck="false"
                            style="height: 120px;"
                            readonly
                            :value="embedCode"
                        ></textarea>
                        <copy-to-clipboard-button :text="embedCode"/>

                        <div class="mt-3">
                            <widget-templates/>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section
            class="card mb-3"
            role="region"
            :data-bs-theme="selectedTheme"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Live Preview') }}
                </h2>
            </div>
            <div class="card-body">
                <div class="preview-container border rounded p-3" :style="previewContainerStyle">
                    <iframe
                        width="100%"
                        :src="embedUrl"
                        frameborder="0"
                        style="border: 0;"
                        :style="previewFrameStyle"
                    ></iframe>
                </div>
            </div>
        </section>
    </modal>
</template>

<script setup lang="ts">
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton.vue";
import {computed, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";
import WidgetTemplates from "~/components/Stations/Profile/Widgets/WidgetTemplates.vue";
import {useWidgetStore} from "~/components/Stations/Profile/Widgets/useWidgetStore.ts";
import {storeToRefs} from "pinia";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";

const stationData = useStationData();
const profileData = useStationProfileData();

const $store = useWidgetStore();
const {
    selectedType,
    selectedTheme,
    customWidth,
    customHeight,
    customization,
} = storeToRefs($store);
const {
    $reset: resetStore
} = $store;

const {$gettext} = useTranslate();

const encodeCustomCssParam = (css: string): string | null => {
    if (!css) {
        return null;
    }

    const encoder = typeof globalThis.btoa === 'function' ? globalThis.btoa : null;
    if (!encoder) {
        return null;
    }

    try {
        const utf8 = encodeURIComponent(css).replace(/%([0-9A-F]{2})/g, (_match, hex) => {
            return String.fromCharCode(Number.parseInt(hex, 16));
        });
        return encoder(utf8);
    } catch (error) {
        console.warn('Failed to encode custom CSS.', error);
        return null;
    }
};

const types = computed(() => {
    const types = [
        {
            value: 'player',
            text: $gettext('Radio Player')
        },
        {
            value: 'history',
            text: $gettext('History')
        },
        {
            value: 'podcasts',
            text: $gettext('Podcasts')
        },
        {
            value: 'schedule',
            text: $gettext('Schedule')
        }
    ];

    if (stationData.value.features.requests && stationData.value.enableRequests) {
        types.push({
            value: 'requests',
            text: $gettext('Requests')
        });
    }

    if (stationData.value.enableOnDemand) {
        types.push({
            value: 'ondemand',
            text: $gettext('On-Demand Media')
        });
    }

    return types;
});

const themes = computed(() => {
    return [
        {
            value: 'browser',
            text: $gettext('Browser Default')
        },
        {
            value: 'light',
            text: $gettext('Light')
        },
        {
            value: 'dark',
            text: $gettext('Dark')
        }
    ];
});

const layoutOptions = computed(() => {
    return [
        {
            value: 'horizontal',
            text: $gettext('Horizontal')
        },
        {
            value: 'vertical',
            text: $gettext('Vertical')
        },
        {
            value: 'compact',
            text: $gettext('Compact')
        },
        {
            value: 'large',
            text: $gettext('Large')
        }
    ];
});

const baseEmbedUrl = computed(() => {
    switch (selectedType.value) {
        case 'history':
            return profileData.value.publicHistoryEmbedUrl;

        case 'ondemand':
            return profileData.value.publicOnDemandEmbedUrl;

        case 'requests':
            return profileData.value.publicRequestEmbedUrl;

        case 'schedule':
            return profileData.value.publicScheduleEmbedUrl;

        case 'podcasts':
            return profileData.value.publicPodcastsEmbedUrl;

        case 'player':
        default:
            return profileData.value.publicPageEmbedUrl;
    }
});

const embedUrl = computed(() => {
    const baseUrl = new URL(baseEmbedUrl.value);

    // Basic theme
    if (selectedTheme.value !== 'browser') {
        baseUrl.searchParams.set('theme', selectedTheme.value);
    }

    // Player-specific customizations
    if (selectedType.value === 'player') {
        const settings = customization.value;

        // Colors
        if ('primaryColor' in settings && settings.primaryColor && settings.primaryColor !== '#2196F3') {
            baseUrl.searchParams.set('primary_color', settings.primaryColor.replace('#', ''));
        }
        if ('backgroundColor' in settings && settings.backgroundColor && settings.backgroundColor !== '#ffffff') {
            baseUrl.searchParams.set('bg_color', settings.backgroundColor.replace('#', ''));
        }
        if ('textColor' in settings && settings.textColor && settings.textColor !== '#000000') {
            baseUrl.searchParams.set('text_color', settings.textColor.replace('#', ''));
        }

        // Functionality
        if (settings.autoplay) {
            baseUrl.searchParams.set('autoplay', '1');
        }
        if (!settings.showAlbumArt) {
            baseUrl.searchParams.set('hide_album_art', '1');
        }
        if (!settings.showVolumeControls) {
            baseUrl.searchParams.set('hide_volume', '1');
        }
        if (!settings.showTrackProgress) {
            baseUrl.searchParams.set('hide_progress', '1');
        }
        if (!settings.showStreamSelection) {
            baseUrl.searchParams.set('hide_streams', '1');
        }
        if ('initialVolume' in settings && settings.initialVolume && settings.initialVolume !== 75) {
            baseUrl.searchParams.set('volume', settings.initialVolume.toString());
        }

        // Layout
        if ('layout' in settings && settings.layout && settings.layout !== 'horizontal') {
            baseUrl.searchParams.set('layout', settings.layout);
        }
        if (settings.roundedCorners) {
            baseUrl.searchParams.set('rounded', '1');
        }

        // Advanced
        if (settings.enablePopupPlayer) {
            baseUrl.searchParams.set('allow_popup', '1');
        }
        if (settings.continuousPlay) {
            baseUrl.searchParams.set('continuous', '1');
        }
        if (settings.customCss) {
            const encodedCss = encodeCustomCssParam(settings.customCss);
            if (encodedCss) {
                baseUrl.searchParams.set('custom_css', encodedCss);
            }
        }
    }

    return baseUrl.toString();
});

const embedHeight = computed(() => {
    switch (selectedType.value) {
        case 'ondemand':
        case 'podcasts':
            return '400px';

        case 'requests':
            return '850px';

        case 'history':
            return '300px';

        case 'schedule':
            return '800px'

        case 'player':
        default:
            // Use custom height if specified, otherwise use layout-based defaults
            if (customHeight.value && customHeight.value > 0) {
                return customHeight.value + 'px';
            }

            switch (customization.value.layout) {
                case 'large':
                    return '250px';
                case 'vertical':
                    return '200px';
                case 'compact':
                    return '80px';
                case 'horizontal':
                default:
                    return '150px';
            }
    }
});

const embedCode = computed(() => {
    const width = customWidth.value || '100%';
    const height = embedHeight.value;
    const isScrollableLayout = customization.value.layout === 'vertical' || customization.value.layout === 'large';
    const minHeightStyle = isScrollableLayout ? '' : ` min-height: ${height};`;
    const heightStyle = ` height: ${height};`;

    return `<iframe src="${embedUrl.value}" frameborder="0" allowtransparency="true" style="width: ${width};${minHeightStyle}${heightStyle} border: 0;"></iframe>`;
});

// Preview styles
const previewContainerStyle = computed(() => {
    const styles: Record<string, string> = {};

    if (selectedType.value === 'player') {
        if (customWidth.value && customWidth.value !== '100%') {
            styles.width = String(customWidth.value);
        }
        styles.maxWidth = '100%';
        styles.margin = '0 auto';
    }

    return styles;
});

const previewFrameStyle = computed(() => {
    const styles: Record<string, string> = {
        width: '100%',
        height: embedHeight.value,
        border: '0'
    };

    return styles;
});

type ModalExpose = {
    show(): void;
    hide(): void;
};

const modalRef = useTemplateRef<ModalExpose>('modalRef');
const {show: open} = useHasModal(modalRef);

defineExpose({
    open
});
</script>
