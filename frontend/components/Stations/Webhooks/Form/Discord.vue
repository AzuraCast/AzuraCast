<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_webhook_url"
                class="col-md-12"
                :field="v$.config.webhook_url"
                input-type="url"
                :label="$gettext('Discord Web Hook URL')"
                :description="$gettext('This URL is provided within the Discord application.')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_content"
                class="col-md-6"
                :field="v$.config.content"
                input-type="textarea"
                :label="$gettext('Main Message Content')"
            />

            <form-group-field
                id="form_config_title"
                class="col-md-6"
                :field="v$.config.title"
                :label="$gettext('Title')"
            />

            <form-group-field
                id="form_config_description"
                class="col-md-6"
                :field="v$.config.description"
                input-type="textarea"
                :label="$gettext('Description')"
            />

            <form-group-field
                id="form_config_url"
                class="col-md-6"
                :field="v$.config.url"
                input-type="url"
                :label="$gettext('URL')"
            />

            <form-group-field
                id="form_config_author"
                class="col-md-6"
                :field="v$.config.author"
                :label="$gettext('Author')"
            />

            <form-group-field
                id="form_config_thumbnail"
                class="col-md-6"
                :field="v$.config.thumbnail"
                input-type="url"
                :label="$gettext('Thumbnail Image URL')"
            />

            <form-group-field
                id="form_config_footer"
                class="col-md-6"
                :field="v$.config.footer"
                :label="$gettext('Footer Text')"
            />

            <form-group-field
                id="form_config_color" 
                class="col-md-6" 
                :field="v$.config.color"
                :label="$gettext('Embed Color (Hex)')"
            />

            <div class="col-md-12">
                <div class="form-check mb-2">
                    <input 
                        id="form_config_include_timestamp"
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="v$.config.include_timestamp.$model"
                    >
                    <label class="form-check-label" for="form_config_include_timestamp">
                        {{ $gettext('Include Timestamp') }}
                    </label>
                </div>
                <small class="form-text text-muted">
                    {{ $gettext('If set, the time sent will be included in the embed footer.') }}
                </small>
            </div>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {helpers, required} from "@vuelidate/validators";
import {useTranslate} from "~/vendor/gettext";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

defineProps<WebhookComponentProps>();

const form = defineModel<ApiGenericForm>('form', { required: true });


const {$gettext} = useTranslate();
const hexColor = helpers.withMessage(
    $gettext('This field must be a valid, non-transparent 6-character hex color.'),
    (value: string) => value === '' || /^#?[0-9A-F]{6}$/i.test(value)
);

const {v$, tabClass} = useVuelidateOnFormTab(
    form,
    {
        config: {
            webhook_url: {required},
            content: {},
            title: {},
            description: {},
            url: {},
            author: {},
            thumbnail: {},
            footer: {},
            color: {hexColor},
            include_timestamp: {}
        }
    },
    () => ({
        config: {
            webhook_url: '',
            content: $gettext(
                'Now playing on %{station}:',
                {'station': '{{ station.name }}'}
            ),
            title: '{{ now_playing.song.title }}',
            description: '{{ now_playing.song.artist }}',
            url: '{{ station.listen_url }}',
            author: '{{ live.streamer_name }}',
            thumbnail: '{{ now_playing.song.art }}',
            footer: $gettext('Powered by AzuraCast'),
            color: '#3498DB',
            include_timestamp: true
        }
    })
);
</script>
