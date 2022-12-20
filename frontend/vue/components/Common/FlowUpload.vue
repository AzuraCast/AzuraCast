<template>
    <div class="flow-upload">
        <div class="upload-progress">
            <template v-for="(file, key) in files.value" :key="key">
                <div v-if="file.isVisible" class="uploading-file pt-1" :id="'file_upload_' + file.uniqueIdentifier"
                     :class="{ 'text-success': file.isCompleted, 'text-danger': file.error }">
                    <h6 class="fileuploadname m-0">{{ file.name }}</h6>
                    <div v-if="!file.isCompleted" class="progress h-15 my-1">
                        <div class="progress-bar h-15" role="progressbar" :style="{width: file.progressPercent+'%'}"
                             :aria-valuenow="file.progressPercent" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <div class="upload-status" v-if="file.error">
                        {{ file.error }}
                    </div>
                    <div class="size">{{ file.size }}</div>
                </div>
            </template>
        </div>
        <div class="file-drop-target" ref="file_drop_target">
            {{ $gettext('Drag file(s) here to upload or') }}
            <button ref="file_browse_target" class="file-upload btn btn-primary text-center ml-1" type="button">
                <icon icon="cloud_upload"></icon>
                {{ $gettext('Select File') }}
            </button>
            <small class="file-name"></small>
            <input type="file" :accept="validMimeTypesList" :multiple="allowMultiple"
                   style="visibility: hidden; position: absolute;"/>
        </div>
    </div>
</template>

<style lang="scss">
div.flow-upload {
    div.upload-progress {
        padding: 4px 0;

        & > div {
            padding: 3px 0;
        }

        .error {
            color: #a00;
        }

        .progress {
            margin-bottom: 5px;

            .progress-bar {
                border-bottom-width: 10px;

                &::after {
                    height: 10px;
                }
            }
        }
    }

    div.file-drop-target {
        padding: 25px 0;
        text-align: center;

        input {
            display: inline;
        }
    }
}
</style>

<script setup>
import formatFileSize from '~/functions/formatFileSize.js';
import Icon from './Icon';
import _ from 'lodash';
import {computed, onMounted, onUnmounted, reactive, ref} from "vue";
import Flow from "@flowjs/flow.js";

const props = defineProps({
    targetUrl: String,
    allowMultiple: {
        type: Boolean,
        default: false
    },
    validMimeTypes: {
        type: Array,
        default() {
            return ['*'];
        }
    },
    flowConfiguration: {
        type: Object,
        default() {
            return {};
        }
    }
});

const emit = defineEmits(['complete', 'success', 'error']);

const validMimeTypesList = computed(() => {
    return props.validMimeTypes.join(', ');
});

let flow = null;

const files = reactive({
    value: {},
    push(file) {
        this.value[file.uniqueIdentifier] = {
            name: file.name,
            uniqueIdentifier: file.uniqueIdentifier,
            size: formatFileSize(file.size),
            isVisible: true,
            isCompleted: false,
            progressPercent: 0,
            error: null
        };
    },
    get(file) {
        return this.value[file.uniqueIdentifier] ?? {};
    },
    hideAll() {
        _.forEach(this.value, (file) => {
            file.isVisible = false;
        });
    },
    reset() {
        this.value = {};
    }
});

const file_browse_target = ref(); // Template Ref
const file_drop_target = ref(); // Template Ref

onMounted(() => {
    let defaultConfig = {
        target: () => {
            return props.targetUrl
        },
        singleFile: !props.allowMultiple,
        headers: {
            'Accept': 'application/json',
            'X-API-CSRF': App.api_csrf
        },
        withCredentials: true,
        allowDuplicateUploads: true,
        fileParameterName: 'file_data',
        uploadMethod: 'POST',
        testMethod: 'GET',
        method: 'multipart',
        maxChunkRetries: 3,
        testChunks: false
    };
    let config = _.defaultsDeep({}, props.flowConfiguration, defaultConfig);

    flow = new Flow(config);

    flow.assignBrowse(file_browse_target.value);
    flow.assignDrop(file_drop_target.value);

    flow.on('fileAdded', (file) => {
        files.push(file);
        return true;
    });

    flow.on('filesSubmitted', () => {
        flow.upload();
    });

    flow.on('fileProgress', (file) => {
        files.get(file).progressPercent = parseInt(file.progress() * 100);
    });

    flow.on('fileSuccess', (file, message) => {
        files.get(file).isCompleted = true;

        let messageJson = JSON.parse(message);
        emit('success', file, messageJson);
    });

    flow.on('error', (message, file, chunk) => {
        console.error(message, file, chunk);

        let messageText = this.$gettext('Could not upload file.');
        try {
            if (typeof message !== 'undefined') {
                let messageJson = JSON.parse(message);
                if (typeof messageJson.message !== 'undefined') {
                    messageText = messageJson.message;
                    if (messageText.indexOf(': ') > -1) {
                        messageText = messageText.split(': ')[1];
                    }
                }
            }
        } catch (e) {
        }

        files.get(file).error = messageText;
        emit('error', file, messageText);
    });

    flow.on('complete', () => {
        files.hideAll();

        emit('complete');
    });
});

onUnmounted(() => {
    flow = null;
    files.reset();
});
</script>
