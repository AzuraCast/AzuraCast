<template>
    <form-fieldset>
        <div class="row g-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Name') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_description"
                class="col-md-12"
                :field="form.description"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('Description') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_genre"
                class="col-md-6"
                :field="form.genre"
            >
                <template #label>
                    {{ $gettext('Genre') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_url"
                class="col-md-6"
                :field="form.url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Web Site URL') }}
                </template>
                <template #description>
                    {{
                        $gettext('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_timezone"
                class="col-md-12"
                :field="form.timezone"
            >
                <template #label>
                    {{ $gettext('Time Zone') }}
                </template>
                <template #description>
                    {{
                        $gettext('Scheduled playlists and other timed items will be controlled by this time zone.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-select
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :options="timezoneOptions"
                    />
                </template>
            </form-group-field>

            <form-group-field
                v-if="showAdvanced"
                id="edit_form_short_name"
                class="col-md-6"
                :field="form.short_name"
                advanced
            >
                <template #label>
                    {{ $gettext('URL Stub') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally specify a short URL-friendly name, such as "my_station_name", that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                v-if="showAdvanced"
                id="edit_form_api_history_items"
                class="col-md-6"
                :field="form.api_history_items"
                advanced
            >
                <template #label>
                    {{ $gettext('Number of Visible Recent Songs') }}
                </template>
                <template #description>
                    {{
                        $gettext('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-select
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :options="historyItemsOptions"
                    />
                </template>
            </form-group-field>
        </div>
    </form-fieldset>

    <form-fieldset>
        <template #label>
            {{ $gettext('Public Pages') }}
        </template>

        <div class="row g-3">
            <form-group-checkbox
                id="edit_form_enable_public_page"
                class="col-md-12"
                :field="form.enable_public_page"
            >
                <template #label>
                    {{ $gettext('Enable Public Pages') }}
                </template>
                <template #description>
                    {{ $gettext('Show the station in public pages and general API results.') }}
                </template>
            </form-group-checkbox>
        </div>
    </form-fieldset>

    <form-fieldset>
        <template #label>
            {{ $gettext('On-Demand Streaming') }}
        </template>

        <div class="row g-3">
            <form-group-checkbox
                id="edit_form_enable_on_demand"
                class="col-md-12"
                :field="form.enable_on_demand"
            >
                <template #label>
                    {{ $gettext('Enable On-Demand Streaming') }}
                </template>
                <template #description>
                    {{
                        $gettext('If enabled, music from playlists with on-demand streaming enabled will be available to stream via a specialized public page.')
                    }}
                </template>
            </form-group-checkbox>

            <form-group-checkbox
                v-if="form.enable_on_demand.$model"
                id="edit_form_enable_on_demand_download"
                class="col-md-12"
                :field="form.enable_on_demand_download"
            >
                <template #label>
                    {{ $gettext('Enable Downloads on On-Demand Page') }}
                </template>
                <template #description>
                    {{
                        $gettext('If enabled, a download button will also be present on the public "On-Demand" page.')
                    }}
                </template>
            </form-group-checkbox>
        </div>
    </form-fieldset>
</template>

<script setup>
import FormFieldset from "~/components/Form/FormFieldset";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    timezones: {
        type: Object,
        required: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const timezoneOptions = computed(() => {
    return objectToFormOptions(props.timezones);
});

const {$gettext} = useTranslate();

const historyItemsOptions = computed(() => {
    return [
        {
            text: $gettext('Disabled'),
            value: 0,
        },
        {text: '1', value: 1},
        {text: '5', value: 5},
        {text: '10', value: 10},
        {text: '15', value: 15}
    ];
});
</script>
