<template>
    <tab :label="$gettext('Intro')">
        <div class="row g-3">
            <form-group
                id="intro_file"
                class="col-md-6"
            >
                <template #label>
                    {{ $gettext('Select Intro File') }}
                </template>
                <template #description>
                    {{
                        $gettext('This introduction file should exactly match the bitrate and format of the mount point itself.')
                    }}
                </template>

                <flow-upload
                    :target-url="targetUrl"
                    :valid-mime-types="['audio/*']"
                    @success="onFileSuccess"
                />
            </form-group>

            <form-markup
                id="current_intro_file"
                class="col-md-6"
            >
                <template #label>
                    {{ $gettext('Current Intro File') }}
                </template>

                <div v-if="hasIntro">
                    <div class="block-buttons pt-3">
                        <a
                            v-if="editIntroUrl"
                            class="btn btn-block btn-dark"
                            :href="editIntroUrl"
                            target="_blank"
                        >
                            {{ $gettext('Download') }}
                        </a>
                        <button
                            type="button"
                            class="btn btn-block btn-danger"
                            @click="deleteIntro"
                        >
                            {{ $gettext('Clear File') }}
                        </button>
                    </div>
                </div>
                <div v-else>
                    {{ $gettext('There is no existing intro file associated with this mount point.') }}
                </div>
            </form-markup>
        </div>
    </tab>
</template>

<script setup lang="ts">
import FlowUpload from '~/components/Common/FlowUpload.vue';

import {computed, toRef} from "vue";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    modelValue: {
        type: Object,
        default: null
    },
    recordHasIntro: {
        type: Boolean,
        required: true
    },
    editIntroUrl: {
        type: String,
        default: null
    },
    newIntroUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const hasIntro = toRef(props, 'recordHasIntro');

const targetUrl = computed(() => {
    return (props.editIntroUrl)
        ? props.editIntroUrl
        : props.newIntroUrl;
});

const onFileSuccess = (_file, message) => {
    hasIntro.value = true;

    if (!props.editIntroUrl) {
        emit('update:modelValue', message);
    }
};

const {axios} = useAxios();

const deleteIntro = () => {
    if (props.editIntroUrl) {
        axios.delete(props.editIntroUrl).then(() => {
            hasIntro.value = false;
        });
    } else {
        hasIntro.value = false;

        emit('update:modelValue', null);
    }
};
</script>
