<template>
    <b-tab :title="$gettext('Intro')">
        <b-form-group>
            <div class="row g-3">
                <b-form-group
                    class="col-md-6"
                    label-for="intro_file"
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
                </b-form-group>

                <b-form-group class="col-md-6">
                    <template #label>
                        {{ $gettext('Current Intro File') }}
                    </template>

                    <div v-if="hasIntro">
                        <div class="buttons pt-3">
                            <a
                                v-if="editIntroUrl"
                                class="btn btn-block btn-dark"
                                :href="editIntroUrl"
                                target="_blank"
                            >
                                {{ $gettext('Download') }}
                            </a>
                            <button
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
                </b-form-group>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import FlowUpload from '~/components/Common/FlowUpload';

import {computed, toRef} from "vue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    recordHasIntro: {
        type: Boolean,
        required: true
    },
    editIntroUrl: {
        type: String,
        required: true
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

const onFileSuccess = (file, message) => {
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

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    }
}
</script>
