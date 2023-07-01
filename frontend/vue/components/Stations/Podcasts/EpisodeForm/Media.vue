<template>
    <o-tab-item :label="$gettext('Media')">
        <b-form-group>
            <div class="row g-3">
                <b-form-group
                    class="col-md-6"
                    label-for="media_file"
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
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        {{ $gettext('Current Podcast Media') }}
                    </template>

                    <div v-if="hasMedia">
                        <div class="buttons pt-3">
                            <a
                                v-if="downloadUrl"
                                class="btn btn-block btn-dark"
                                :href="downloadUrl"
                                target="_blank"
                            >
                                {{ $gettext('Download') }}
                            </a>
                            <button
                                class="btn btn-block btn-danger"
                                @click="deleteMedia"
                            >
                                {{ $gettext('Clear Media') }}
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        {{ $gettext('There is no existing media associated with this episode.') }}
                    </div>
                </b-form-group>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import FlowUpload from '~/components/Common/FlowUpload';
import {computed, ref, toRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {syncRef} from "@vueuse/core";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    recordHasMedia: {
        type: Boolean,
        required: true
    },
    downloadUrl: {
        type: String,
        required: true
    },
    editMediaUrl: {
        type: String,
        required: true
    },
    newMediaUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const hasMedia = ref(null);
syncRef(toRef(props, 'recordHasMedia'), hasMedia, {direction: 'ltr'});

const targetUrl = computed(() => {
    return (props.editMediaUrl)
        ? props.editMediaUrl
        : props.newMediaUrl;
});

const onFileSuccess = (file, message) => {
    hasMedia.value = true;

    if (!props.editMediaUrl) {
        emit('update:modelValue', message);
    }
};

const {axios} = useAxios();

const deleteMedia = () => {
    if (props.editMediaUrl) {
        axios.delete(props.editMediaUrl).then(() => {
            hasMedia.value = false;
        });
    } else {
        hasMedia.value = false;

        emit('update:modelValue', null);
    }
}
</script>
