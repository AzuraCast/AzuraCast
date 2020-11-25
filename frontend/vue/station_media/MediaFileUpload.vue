<template>
    <div>
        <div id="upload_progress">
            <div class="uploading-file pt-1" v-for="(file, _) in files" :id="'file_upload_' + file.uniqueIdentifier"
                 :class="{ 'text-success': file.is_completed, 'text-danger': file.error }">
                <h6 class="fileuploadname m-0">{{ file.name }}</h6>
                <div class="progress" v-if="!file.is_completed">
                    <div class="progress-bar" :style="{ width: file.progress_percent+'%' }"></div>
                </div>
                <div class="upload-status" v-if="file.error">
                    {{ file.error }}
                </div>
                <div class="size">{{ formatFileSize(file.size) }}</div>
            </div>
        </div>
        <div id="file_drop_target">
            <translate key="lang_upload_target">Drag files here to upload to this folder or</translate>
            <button id="file_browse_target" class="file-upload btn btn-primary text-center ml-1" type="button">
                <translate key="lang_select_file">Select File</translate>
                <i class="material-icons" aria-hidden="true">cloud_upload</i>
            </button>
            <small class="file-name"></small>
            <input type="file" :accept="validMimeTypesList" multiple style="visibility: hidden; position: absolute;"/>
        </div>
    </div>
</template>

<script>
import { formatFileSize } from '../inc/format_file_size';
import Flow from '@flowjs/flow.js';

export default {
    name: 'FileUpload',
    props: {
        uploadUrl: String,
        currentDirectory: String,
        searchPhrase: String,
        validMimeTypes: Array
    },
    data () {
        return {
            flow: null,
            files: []
        };
    },
    mounted () {
        this.flow = new Flow({
            target: this.uploadUrl,
            query: () => {
                return {
                    'currentDirectory': this.currentDirectory,
                    'searchPhrase': this.searchPhrase
                };
            },
            headers: {
                'Accept': 'application/json'
            },
            withCredentials: true,
            allowDuplicateUploads: true,
            fileParameterName: 'file_data'
        });

        this.flow.assignBrowse(document.getElementById('file_browse_target'));
        this.flow.assignDrop(document.getElementById('file_drop_target'));

        this.flow.on('fileAdded', (file, event) => {
            file.progress_percent = 0;
            file.is_completed = false;
            file.error = null;
            file.is_visible = true;

            this.files.push(file);
            return true;
        });

        this.flow.on('filesSubmitted', (array, event) => {
            this.flow.upload();
        });

        this.flow.on('fileProgress', (file) => {
            file.progress_percent = file.progress() * 100;
        });

        this.flow.on('fileSuccess', (file, message) => {
            file.is_completed = true;
        });

        this.flow.on('fileError', (file, message) => {
            let messageJson = JSON.parse(message);
            file.error = messageJson.message;
        });

        this.flow.on('error', (message, file, chunk) => {
            console.error(message, file, chunk);
        });

        this.flow.on('complete', () => {
            this.files = [];
            this.$emit('relist');
        });
    },
    computed: {
        validMimeTypesList () {
            if (this.validMimeTypes) {
                return this.validMimeTypes.join(', ');
            }

            return 'audio/*';
        }
    },
    methods: {
        formatFileSize (bytes) {
            return formatFileSize(bytes);
        }
    }
};
</script>
