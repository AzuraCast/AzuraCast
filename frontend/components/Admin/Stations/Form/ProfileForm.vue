<template>
    <tab
        :label="$gettext('Profile')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('Name')"
            />

            <form-group-field
                id="edit_form_description"
                class="col-md-12"
                :field="r$.description"
                input-type="textarea"
                :label="$gettext('Description')"
            />

            <form-group-field
                id="edit_form_genre"
                class="col-md-6"
                :field="r$.genre"
                :label="$gettext('Genre')"
            >
                <template #description>
                    {{
                        $gettext('The primary genre this station plays, such as "rock", "electronic", or "talk".')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_url"
                class="col-md-6"
                :field="r$.url"
                input-type="url"
                :label="$gettext('Web Site URL')"
                :description="$gettext('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.')"
            />

            <div class="col-md-6">
                <div class="row g-3">
                    <form-group-select
                        id="edit_form_timezone"
                        class="col-md-12"
                        :field="r$.timezone"
                        :options="timezones"
                        :label="$gettext('Time Zone')"
                        :description="$gettext('Scheduled playlists and other timed items will be controlled by this time zone.')"
                    />

                    <form-group-field
                        id="edit_form_short_name"
                        class="col-md-12"
                        :field="r$.short_name"
                        advanced
                        :label="$gettext('URL Stub')"
                    >
                        <template #description>
                            {{
                                $gettext('Optionally specify a short URL-friendly name, such as "my_station_name", that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.')
                            }}
                        </template>
                    </form-group-field>
                </div>

            </div>

            <form-group-field
                id="edit_form_api_history_items"
                class="col-md-6"
                :field="r$.api_history_items"
                advanced
                :label="$gettext('Number of Visible Recent Songs')"
            >
                <template #description>
                    {{
                        $gettext('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.')
                    }}
                </template>
                <template #default="{id, model}">
                    <radio-with-custom-number
                        :id="id"
                        v-model="model.$model"
                        :options="historyItemsOptions"
                    />
                </template>
            </form-group-field>
        </div>

        <form-fieldset>
            <template #label>
                {{ $gettext('Public Pages') }}
            </template>

            <div class="row g-3">
                <form-group-checkbox
                    id="edit_form_enable_public_page"
                    class="col-md-12"
                    :field="r$.enable_public_page"
                    :label="$gettext('Enable Public Pages')"
                    :description="$gettext('Show the station in public pages and general API results.')"
                />
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
                    :field="r$.enable_on_demand"
                    :label="$gettext('Enable On-Demand Streaming')"
                    :description="$gettext('If enabled, music from playlists with on-demand streaming enabled will be available to stream via a specialized public page.')"
                />

                <form-group-checkbox
                    v-if="form.enable_on_demand"
                    id="edit_form_enable_on_demand_download"
                    class="col-md-12"
                    :field="r$.enable_on_demand_download"
                    :label="$gettext('Enable Downloads on On-Demand Page')"
                >
                    <template #description>
                        {{
                            $gettext('If enabled, a download button will also be present on the public "On-Demand" page.')
                        }}
                    </template>
                </form-group-checkbox>
            </div>
        </form-fieldset>
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import Tab from "~/components/Common/Tab.vue";
import RadioWithCustomNumber from "~/components/Common/RadioWithCustomNumber.vue";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";

defineProps<{
    timezones: Record<string, string>,
}>();

const {r$, form} = storeToRefs(useAdminStationsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.profileTab));

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
