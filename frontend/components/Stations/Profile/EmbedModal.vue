<template>
    <modal
        id="embed_modal"
        ref="$modal"
        size="xl"
        :title="$gettext('Player Embed Widget Builder')"
        hide-footer
        no-enforce-focus
    >
        <div class="row">
            <div class="col-md-8">
                <section
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Widget Builder') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
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

                        <div v-if="selectedType === 'player'" class="mb-3">
                            <h5 class="mb-3">{{ $gettext('Player Customization') }}</h5>
                            
                            <nav class="mb-3">
                                <div class="nav nav-pills" role="tablist">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeCustomizationTab === 'appearance' }"
                                        @click="activeCustomizationTab = 'appearance'"
                                        type="button"
                                    >
                                        {{ $gettext('Appearance') }}
                                    </button>
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeCustomizationTab === 'functionality' }"
                                        @click="activeCustomizationTab = 'functionality'"
                                        type="button"
                                    >
                                        {{ $gettext('Functionality') }}
                                    </button>
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeCustomizationTab === 'layout' }"
                                        @click="activeCustomizationTab = 'layout'"
                                        type="button"
                                    >
                                        {{ $gettext('Layout') }}
                                    </button>
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeCustomizationTab === 'advanced' }"
                                        @click="activeCustomizationTab = 'advanced'"
                                        type="button"
                                    >
                                        {{ $gettext('Advanced') }}
                                    </button>
                                </div>
                            </nav>

                            <div v-if="activeCustomizationTab === 'appearance'" class="tab-content">
                                <div class="row">
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_primary_color"
                                            :field="{}"
                                            :label="$gettext('Primary Color')"
                                        >
                                            <input
                                                v-model="customization.primaryColor"
                                                type="color"
                                                class="form-control form-control-color"
                                            />
                                        </form-group-field>
                                    </div>
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_background_color"
                                            :field="{}"
                                            :label="$gettext('Background Color')"
                                        >
                                            <input
                                                v-model="customization.backgroundColor"
                                                type="color"
                                                class="form-control form-control-color"
                                            />
                                        </form-group-field>
                                    </div>
                                    <div class="col-md-4">
                                        <form-group-field
                                            id="embed_text_color"
                                            :field="{}"
                                            :label="$gettext('Text Color')"
                                        >
                                            <input
                                                v-model="customization.textColor"
                                                type="color"
                                                class="form-control form-control-color"
                                            />
                                        </form-group-field>
                                    </div>
                                </div>
                                <div class="row">
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
                            </div>

                            <div v-if="activeCustomizationTab === 'functionality'" class="tab-content">
                                <div class="row">
                                    <div class="col-md-6">
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
                                    </div>
                                    <div class="col-md-6">
                                        <form-group-checkbox
                                            id="embed_show_stream_selection"
                                            v-model="customization.showStreamSelection"
                                            :label="$gettext('Show Stream Selection')"
                                        />
                                        <form-group-checkbox
                                            id="embed_show_history_button"
                                            v-model="customization.showHistoryButton"
                                            :label="$gettext('Show History Button')"
                                        />
                                        <form-group-checkbox
                                            id="embed_show_request_button"
                                            v-model="customization.showRequestButton"
                                            :label="$gettext('Show Request Button')"
                                        />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <form-group-field
                                            id="embed_initial_volume"
                                            :field="{}"
                                            :label="$gettext('Initial Volume')"
                                        >
                                            <div class="input-group">
                                                <input
                                                    v-model.number="customization.initialVolume"
                                                    type="range"
                                                    class="form-range"
                                                    min="0"
                                                    max="100"
                                                    step="1"
                                                />
                                                <span class="input-group-text">{{ customization.initialVolume }}%</span>
                                            </div>
                                        </form-group-field>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeCustomizationTab === 'layout'" class="tab-content">
                                <div class="row">
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
                                            :field="{}"
                                            :label="$gettext('Width (px or %)')"
                                        >
                                            <input
                                                v-model="customization.width"
                                                type="text"
                                                class="form-control"
                                                placeholder="100%"
                                            />
                                        </form-group-field>
                                        <form-group-field
                                            id="embed_height"
                                            :field="{}"
                                            :label="$gettext('Height (px)')"
                                        >
                                            <input
                                                v-model.number="customization.height"
                                                type="number"
                                                class="form-control"
                                                min="100"
                                                placeholder="150"
                                            />
                                        </form-group-field>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeCustomizationTab === 'advanced'" class="tab-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <form-group-checkbox
                                            id="embed_popup_player"
                                            v-model="customization.enablePopupPlayer"
                                            :label="$gettext('Enable Popup Player')"
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <form-group-checkbox
                                            id="embed_continuous_play"
                                            v-model="customization.continuousPlay"
                                            :label="$gettext('Continuous Play Across Pages')"
                                        />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <form-group-field
                                            id="embed_custom_css"
                                            :field="{}"
                                            :label="$gettext('Custom CSS')"
                                            :description="$gettext('Additional CSS to customize the widget appearance')"
                                        >
                                            <textarea
                                                v-model="customization.customCss"
                                                rows="4"
                                                class="form-control font-monospace"
                                                placeholder=".radio-player-widget { border-radius: 10px; }"
                                            ></textarea>
                                        </form-group-field>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                            class="full-width form-control text-preformatted"
                            spellcheck="false"
                            style="height: 120px;"
                            readonly
                            :value="embedCode"
                        ></textarea>
                        <copy-to-clipboard-button :text="embedCode" />
                        
                        <div class="mt-3">
                            <div class="input-group">
                                <input
                                    v-model="templateName"
                                    type="text"
                                    class="form-control"
                                    :placeholder="$gettext('Template name...')"
                                />
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    @click="saveTemplate"
                                    :disabled="!templateName"
                                >
                                    {{ $gettext('Save') }}
                                </button>
                            </div>
                            <div v-if="savedTemplates.length > 0" class="mt-2">
                                <div class="input-group">
                                    <select
                                        v-model="selectedTemplate"
                                        class="form-select"
                                        @change="loadTemplate"
                                    >
                                        <option value="">{{ $gettext('Load saved template...') }}</option>
                                        <option
                                            v-for="template in savedTemplates"
                                            :key="template.name"
                                            :value="template.name"
                                        >
                                            {{ template.name }}
                                        </option>
                                    </select>
                                    <button
                                        v-if="selectedTemplate"
                                        type="button"
                                        class="btn btn-outline-danger"
                                        @click="deleteTemplate"
                                        :title="$gettext('Delete template')"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-3 border-top pt-3">
                                <small class="text-muted">{{ $gettext('Template Management') }}</small>
                                <div class="mt-2 d-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="exportTemplate"
                                        :disabled="savedTemplates.length === 0"
                                    >
                                        {{ $gettext('Export All') }}
                                    </button>
                                    <label class="btn btn-sm btn-outline-secondary">
                                        {{ $gettext('Import') }}
                                        <input
                                            type="file"
                                            accept=".json"
                                            style="display: none;"
                                            @change="importTemplate"
                                        />
                                    </label>
                                </div>
                            </div>
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
                <div class="preview-container" :style="previewContainerStyle">
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
import {computed, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";

const stationData = useStationData();
const profileData = useStationProfileData();

const selectedType = ref('player');
const selectedTheme = ref('light');
const activeCustomizationTab = ref('appearance');
const templateName = ref('');
const selectedTemplate = ref('');

interface WidgetCustomization {
    primaryColor: string;
    backgroundColor: string;
    textColor: string;
    showAlbumArt: boolean;
    roundedCorners: boolean;
    autoplay: boolean;
    showVolumeControls: boolean;
    showTrackProgress: boolean;
    showStreamSelection: boolean;
    showHistoryButton: boolean;
    showRequestButton: boolean;
    initialVolume: number;
    layout: string;
    width: string;
    height: number;
    enablePopupPlayer: boolean;
    continuousPlay: boolean;
    customCss: string;
}

const customization = ref<WidgetCustomization>({
    primaryColor: '#2196F3',
    backgroundColor: '#ffffff',
    textColor: '#000000',
    showAlbumArt: true,
    roundedCorners: false,
    autoplay: false,
    showVolumeControls: true,
    showTrackProgress: true,
    showStreamSelection: true,
    showHistoryButton: false,
    showRequestButton: false,
    initialVolume: 75,
    layout: 'horizontal',
    width: '100%',
    height: 150,
    enablePopupPlayer: false,
    continuousPlay: false,
    customCss: ''
});

const savedTemplates = ref<Array<{name: string, config: WidgetCustomization}>>([]);

const {$gettext} = useTranslate();

// Load saved templates from localStorage
const loadSavedTemplates = () => {
    try {
        const templates = localStorage.getItem('azuracast_embed_templates');
        if (templates) {
            savedTemplates.value = JSON.parse(templates);
        }
    } catch (e) {
        console.warn('Failed to load embed templates:', e);
    }
};

// Save templates to localStorage
const saveTemplatesToStorage = () => {
    try {
        localStorage.setItem('azuracast_embed_templates', JSON.stringify(savedTemplates.value));
    } catch (e) {
        console.warn('Failed to save embed templates:', e);
    }
};

// Save current configuration as template
const saveTemplate = () => {
    if (!templateName.value.trim()) return;
    
    const template = {
        name: templateName.value.trim(),
        config: { ...customization.value }
    };
    
    const existingIndex = savedTemplates.value.findIndex(t => t.name === template.name);
    if (existingIndex >= 0) {
        savedTemplates.value[existingIndex] = template;
    } else {
        savedTemplates.value.push(template);
    }
    
    saveTemplatesToStorage();
    templateName.value = '';
};

// Load template configuration
const loadTemplate = () => {
    if (!selectedTemplate.value) return;
    
    const template = savedTemplates.value.find(t => t.name === selectedTemplate.value);
    if (template) {
        customization.value = { ...template.config };
    }
};

// Delete template
const deleteTemplate = () => {
    if (!selectedTemplate.value) return;
    
    const index = savedTemplates.value.findIndex(t => t.name === selectedTemplate.value);
    if (index >= 0) {
        savedTemplates.value.splice(index, 1);
        saveTemplatesToStorage();
        selectedTemplate.value = '';
    }
};

// Export template configuration
const exportTemplate = () => {
    const exportData = {
        templates: savedTemplates.value,
        current: customization.value,
        version: '1.0'
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `azuracast-widget-templates-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
};

// Import template configuration
const importTemplate = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target?.result as string);
            if (data.templates && Array.isArray(data.templates)) {
                // Merge with existing templates
                data.templates.forEach((template: any) => {
                    const existingIndex = savedTemplates.value.findIndex(t => t.name === template.name);
                    if (existingIndex >= 0) {
                        savedTemplates.value[existingIndex] = template;
                    } else {
                        savedTemplates.value.push(template);
                    }
                });
                saveTemplatesToStorage();
            }
        } catch (error) {
            console.error('Failed to import templates:', error);
            alert($gettext('Failed to import template file. Please check the file format.'));
        }
    };
    reader.readAsText(file);
};

// Initialize templates on component mount
loadSavedTemplates();

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
        // Colors
        if (customization.value.primaryColor !== '#2196F3') {
            baseUrl.searchParams.set('primary_color', customization.value.primaryColor.replace('#', ''));
        }
        if (customization.value.backgroundColor !== '#ffffff') {
            baseUrl.searchParams.set('bg_color', customization.value.backgroundColor.replace('#', ''));
        }
        if (customization.value.textColor !== '#000000') {
            baseUrl.searchParams.set('text_color', customization.value.textColor.replace('#', ''));
        }

        // Functionality
        if (customization.value.autoplay) {
            baseUrl.searchParams.set('autoplay', '1');
        }
        if (!customization.value.showAlbumArt) {
            baseUrl.searchParams.set('hide_album_art', '1');
        }
        if (!customization.value.showVolumeControls) {
            baseUrl.searchParams.set('hide_volume', '1');
        }
        if (!customization.value.showTrackProgress) {
            baseUrl.searchParams.set('hide_progress', '1');
        }
        if (!customization.value.showStreamSelection) {
            baseUrl.searchParams.set('hide_streams', '1');
        }
        if (customization.value.initialVolume !== 75) {
            baseUrl.searchParams.set('volume', customization.value.initialVolume.toString());
        }

        // Layout
        if (customization.value.layout !== 'horizontal') {
            baseUrl.searchParams.set('layout', customization.value.layout);
        }
        if (customization.value.roundedCorners) {
            baseUrl.searchParams.set('rounded', '1');
        }

        // Advanced
        if (customization.value.enablePopupPlayer) {
            baseUrl.searchParams.set('popup', '1');
        }
        if (customization.value.continuousPlay) {
            baseUrl.searchParams.set('continuous', '1');
        }
        if (customization.value.customCss) {
            baseUrl.searchParams.set('custom_css', btoa(customization.value.customCss));
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
            if (customization.value.height && customization.value.height > 0) {
                return customization.value.height + 'px';
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
    const width = customization.value.width || '100%';
    const height = embedHeight.value;
    
    return `<iframe src="${embedUrl.value}" frameborder="0" allowtransparency="true" style="width: ${width}; min-height: ${height}; border: 0;"></iframe>`;
});

// Preview styles
const previewContainerStyle = computed(() => {
    const styles: Record<string, string> = {};
    
    if (selectedType.value === 'player') {
        if (customization.value.width && customization.value.width !== '100%') {
            styles.width = customization.value.width;
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

const $modal = useTemplateRef('$modal');
const {show: open} = useHasModal($modal);

defineExpose({
    open
});
</script>