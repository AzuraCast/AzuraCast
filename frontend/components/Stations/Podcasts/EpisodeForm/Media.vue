<template>
    <tab :label="$gettext('Media')">
        <div class="row g-3">
            <form-group
                id="media_file"
                class="col-md-6"
            >
                <template #label>
                    {{ $gettext('Select Media File') }}
                </template>
                <template #description>
                    {{
                        $gettext('Podcast media should be in the MP3 or M4A (AAC) format for the greatest compatibility.')
                    }}
                </template>

                <flow-upload
                    :target-url="targetUrl"
                    :valid-mime-types="['audio/x-m4a', 'audio/mpeg']"
                    @success="onFileSuccess"
                />
            </form-group>

            <form-markup
                id="current_podcast_media"
                class="col-md-6"
            >
                <template #label>
                    {{ $gettext('Current Podcast Media') }}
                </template>

                <template v-if="hasMedia">
                    <div class="block-buttons pt-3">
                        <a
                            v-if="editMediaUrl"
                            class="btn btn-block btn-dark"
                            :href="editMediaUrl"
                            target="_blank"
                        >
                            {{ $gettext('Download') }}
                        </a>
                        <button
                            type="button"
                            class="btn btn-block btn-danger"
                            @click="deleteMedia"
                        >
                            {{ $gettext('Clear Media') }}
                        </button>
                    </div>
                </template>
                <div v-else>
                    {{ $gettext('There is no existing media associated with this episode.') }}
                </div>
            </form-markup>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FlowUpload, {UploadResponseBody} from "~/components/Common/FlowUpload.vue";
import {computed, ref, toRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {syncRef} from "@vueuse/core";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps<{
    recordHasMedia: boolean,
    editMediaUrl: string,
    newMediaUrl: string,
}>();

const model = defineModel<UploadResponseBody | null>();

const hasMedia = ref<boolean | null>(null);
syncRef(toRef(props, 'recordHasMedia'), hasMedia, {direction: 'ltr'});

const targetUrl = computed(() => {
    return (props.editMediaUrl)
        ? props.editMediaUrl
        : props.newMediaUrl;
});

const onFileSuccess = (_file: any, message: UploadResponseBody | null) => {
    hasMedia.value = true;

    if (!props.editMediaUrl && message) {
        model.value = message;
    }
};

const {axios} = useAxios();

const deleteMedia = async () => {
    if (props.editMediaUrl) {
        await axios.delete(props.editMediaUrl);

        hasMedia.value = false;
    } else {
        hasMedia.value = false;
        model.value = null;
    }
}
</script>
