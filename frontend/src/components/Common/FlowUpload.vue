<template>
    <div class="flow-upload">
        <div class="upload-progress">
            <template
                v-for="(file, key) in files.value"
                :key="key"
            >
                <div
                    v-if="file.isVisible"
                    :id="'file_upload_' + file.uniqueIdentifier"
                    class="uploading-file pt-1"
                    :class="{ 'text-success': file.isCompleted, 'text-danger': file.error }"
                >
                    <h6 class="fileuploadname m-0">
                        {{ file.name }}
                    </h6>
                    <div
                        v-if="!file.isCompleted"
                        class="progress h-20 my-1"
                    >
                        <div
                            class="progress-bar h-20"
                            role="progressbar"
                            :style="{width: file.progressPercent+'%'}"
                            :aria-valuenow="file.progressPercent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        />
                    </div>

                    <div
                        v-if="file.error"
                        class="upload-status"
                    >
                        {{ file.error }}
                    </div>
                    <div class="size">
                        {{ file.size }}
                    </div>
                </div>
            </template>
        </div>
        <div
            ref="$fileDropTarget"
            class="file-drop-target"
        >
            {{ $gettext('Drag file(s) here to upload or') }}
            <button
                ref="$fileBrowseTarget"
                type="button"
                class="file-upload btn btn-primary text-center ms-1"
            >
                <icon :icon="IconUpload" />
                <span>
                    {{ $gettext('Select File') }}
                </span>
            </button>
            <small class="file-name" />
            <input
                type="file"
                :accept="validMimeTypesList"
                :multiple="allowMultiple"
                style="visibility: hidden; position: absolute;"
            >
        </div>
    </div>
</template>

<script setup lang="ts">
import formatFileSize from '~/functions/formatFileSize';
import Icon from './Icon.vue';
import {defaultsDeep, forEach, toInteger} from 'lodash';
import {computed, onMounted, onUnmounted, reactive, ref} from "vue";
import Flow from "@flowjs/flow.js";
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import {IconUpload} from "~/components/Common/icons";

const props = defineProps({
    targetUrl: {
        type: String,
        required: true
    },
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

interface FlowFile {
    uniqueIdentifier: string,
    isVisible: boolean,
    name: string,
    isCompleted: boolean,
    progressPercent: number,
    error?: string,
    size: string
}

interface OriginalFlowFile {
    uniqueIdentifier: string,
    name: string,
    size: number,
    progress(): number
}

const emit = defineEmits(['complete', 'success', 'error']);

const validMimeTypesList = computed(() => {
    return props.validMimeTypes.join(', ');
});

let flow = null;

const files = reactive<{
    value: {
        [key: string]: FlowFile
    },
    push(file: OriginalFlowFile): void,
    get(file: OriginalFlowFile): FlowFile,
    hideAll(): void,
    reset(): void
}>({
    value: {},
    push(file: OriginalFlowFile): void {
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
    get(file: OriginalFlowFile): FlowFile {
        return this.value[file.uniqueIdentifier] ?? {};
    },
    hideAll() {
        forEach(this.value, (file: FlowFile) => {
            file.isVisible = false;
        });
    },
    reset() {
        this.value = {};
    }
});

const $fileBrowseTarget = ref<HTMLButtonElement | null>(null);
const $fileDropTarget = ref<HTMLDivElement | null>(null);

const {apiCsrf} = useAzuraCast();

const {$gettext} = useTranslate();

onMounted(() => {
    const defaultConfig = {
        target: () => {
            return props.targetUrl
        },
        singleFile: !props.allowMultiple,
        headers: {
            'Accept': 'application/json',
            'X-API-CSRF': apiCsrf
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
    const config = defaultsDeep({}, props.flowConfiguration, defaultConfig);

    flow = new Flow(config);

    flow.assignBrowse($fileBrowseTarget.value);
    flow.assignDrop($fileDropTarget.value);

    flow.on('fileAdded', (file: OriginalFlowFile) => {
        files.push(file);
        return true;
    });

    flow.on('filesSubmitted', () => {
        flow.upload();
    });

    flow.on('fileProgress', (file: OriginalFlowFile) => {
      files.get(file).progressPercent = toInteger(file.progress() * 100);
    });

    flow.on('fileSuccess', (file: OriginalFlowFile, message) => {
        files.get(file).isCompleted = true;

        const messageJson = JSON.parse(message);
        emit('success', file, messageJson);
    });

    flow.on('error', (message, file: OriginalFlowFile, chunk) => {
        console.error(message, file, chunk);

        let messageText = $gettext('Could not upload file.');
        try {
            if (typeof message !== 'undefined') {
                const messageJson = JSON.parse(message);
                if (typeof messageJson.message !== 'undefined') {
                    messageText = messageJson.message;
                    if (messageText.indexOf(': ') > -1) {
                        messageText = messageText.split(': ')[1];
                    }
                }
            }
        } catch (e) {
            // Noop
        }

        files.get(file).error = messageText;
        emit('error', file, messageText);
    });

    flow.on('complete', () => {
        setTimeout(() => {
            files.hideAll();
        }, 2000);

        emit('complete');
    });
});

onUnmounted(() => {
    flow = null;
    files.reset();
});
</script>

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
