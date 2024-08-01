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
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "./Common/FormattingInfo.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import {useTranslate} from "~/vendor/gettext";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {$gettext} = useTranslate();

const {v$, tabClass} = useVuelidateOnFormTab(
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
        }
    },
    form,
    () => {
        return {
            config: {
                webhook_url: '',
                content: $gettext(
                    'Now playing on %{ station }:',
                    {'station': '{{ station.name }}'}
                ),
                title: '{{ now_playing.song.title }}',
                description: '{{ now_playing.song.artist }}',
                url: '{{ station.listen_url }}',
                author: '{{ live.streamer_name }}',
                thumbnail: '{{ now_playing.song.art }}',
                footer: $gettext('Powered by AzuraCast'),
            }
        }
    }
);
</script>
