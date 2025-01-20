<template>
    <tab
        :label="$gettext('Profile')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('Name')"
            />

            <form-group-field
                id="edit_form_description"
                class="col-md-12"
                :field="v$.description"
                input-type="textarea"
                :label="$gettext('Description')"
            />

            <form-group-field
                id="edit_form_genre"
                class="col-md-6"
                :field="v$.genre"
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
                :field="v$.url"
                input-type="url"
                :label="$gettext('Web Site URL')"
                :description="$gettext('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.')"
            />

            <form-group-select
                id="edit_form_timezone"
                class="col-md-12"
                :field="v$.timezone"
                :options="timezones"
                :label="$gettext('Time Zone')"
                :description="$gettext('Scheduled playlists and other timed items will be controlled by this time zone.')"
            />

            <form-group-field
                v-if="enableAdvancedFeatures"
                id="edit_form_short_name"
                class="col-md-6"
                :field="v$.short_name"
                advanced
                :label="$gettext('URL Stub')"
            >
                <template #description>
                    {{
                        $gettext('Optionally specify a short URL-friendly name, such as "my_station_name", that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.')
                    }}
                </template>
            </form-group-field>

            <form-group-select
                v-if="enableAdvancedFeatures"
                id="edit_form_api_history_items"
                class="col-md-6"
                :field="v$.api_history_items"
                advanced
                :options="historyItemsOptions"
                :label="$gettext('Number of Visible Recent Songs')"
            >
                <template #description>
                    {{
                        $gettext('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.')
                    }}
                </template>
            </form-group-select>
        </div>

        <form-fieldset>
            <template #label>
                {{ $gettext('Public Pages') }}
            </template>

            <div class="row g-3">
                <form-group-checkbox
                    id="edit_form_enable_public_page"
                    class="col-md-12"
                    :field="v$.enable_public_page"
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
                    :field="v$.enable_on_demand"
                    :label="$gettext('Enable On-Demand Streaming')"
                    :description="$gettext('If enabled, music from playlists with on-demand streaming enabled will be available to stream via a specialized public page.')"
                />

                <form-group-checkbox
                    v-if="form.enable_on_demand"
                    id="edit_form_enable_on_demand_download"
                    class="col-md-12"
                    :field="v$.enable_on_demand_download"
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
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required, url} from "@vuelidate/validators";
import {useAzuraCast} from "~/vendor/azuracast";
import Tab from "~/components/Common/Tab.vue";

interface StationProfileFormProps extends FormTabProps {
    timezones: Record<string, string>,
}

const props = defineProps<StationProfileFormProps>();
const emit = defineEmits<FormTabEmits>();

const {enableAdvancedFeatures} = useAzuraCast();

const {form, v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    computed(() => {
        let validations: {
            [key: string | number]: any
        } = {
            name: {required},
            description: {},
            genre: {},
            url: {url},
            timezone: {},
            enable_public_page: {},
            enable_on_demand: {},
            enable_on_demand_download: {},
        };

        if (enableAdvancedFeatures) {
            validations = {
                ...validations,
                short_name: {},
                api_history_items: {},
            };
        }

        return validations;
    }),
    () => {
        let blankForm: {
            [key: string | number]: any
        } = {
            name: '',
            description: '',
            genre: '',
            url: '',
            timezone: 'UTC',
            enable_public_page: true,
            enable_on_demand: false,
            enable_on_demand_download: true,
        };

        if (enableAdvancedFeatures) {
            blankForm = {
                ...blankForm,
                short_name: '',
                api_history_items: 5,
            }
        }

        return blankForm;
    }
);

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
