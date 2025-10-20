<template>
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
                {{ $gettext('Delete') }}
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
</template>

<script setup lang="ts">
import {ref} from "vue";
import {useWidgetStore, WidgetTemplate} from "~/components/Stations/Profile/Widgets/useWidgetStore.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {useLocalStorage} from "@vueuse/core";

const $store = useWidgetStore();
const {
    getTemplate,
    setFromTemplate
} = $store;

const templateName = ref('');
const selectedTemplate = ref('');

type SavedTemplate = {
    name: string,
    config: WidgetTemplate
};
const savedTemplates = useLocalStorage<SavedTemplate[]>(
    'azuracast_embed_templates',
    []
);

const {$gettext} = useTranslate();

// Save current configuration as template
const saveTemplate = () => {
    if (!templateName.value.trim()) return;

    const template: SavedTemplate = {
        name: templateName.value.trim(),
        config: getTemplate()
    };

    const existingIndex = savedTemplates.value.findIndex(t => t.name === template.name);
    if (existingIndex >= 0) {
        savedTemplates.value[existingIndex] = template;
    } else {
        savedTemplates.value.push(template);
    }

    templateName.value = '';
};

// Load template configuration
const loadTemplate = () => {
    if (!selectedTemplate.value) return;

    const template = savedTemplates.value.find(t => t.name === selectedTemplate.value);
    if (template) {
        setFromTemplate(template.config);
    }
};

// Delete template
const deleteTemplate = () => {
    if (!selectedTemplate.value) return;

    const index = savedTemplates.value.findIndex(t => t.name === selectedTemplate.value);
    if (index >= 0) {
        savedTemplates.value.splice(index, 1);
        selectedTemplate.value = '';
    }
};

// Export template configuration
const exportTemplate = () => {
    const exportData = {
        templates: savedTemplates.value,
        version: '1.0'
    };

    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
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
            }
        } catch (error) {
            console.error('Failed to import templates:', error);
            alert($gettext('Failed to import template file. Please check the file format.'));
        }
    };
    reader.readAsText(file);
};
</script>
