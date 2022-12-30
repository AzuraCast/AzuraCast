<template>
    <flow-upload
        :target-url="uploadUrl"
        :flow-configuration="flowConfiguration"
        :valid-mime-types="validMimeTypes"
        allow-multiple
        @complete="onFlowUpload"
        @error="onFlowUpload"
    />
</template>

<script>
import FlowUpload from '~/components/Common/FlowUpload';

export default {
    name: 'FileUpload',
    components: {FlowUpload},
    props: {
        uploadUrl: {
            type: String,
            required: true
        },
        currentDirectory: {
            type: String,
            required: true
        },
        searchPhrase: {
            type: String,
            required: true
        },
        validMimeTypes: {
            type: Array,
            default() {
                return ['audio/*'];
            }
        }
    },
    emits: ['relist'],
    data() {
        return {
            flow: null,
            files: []
        };
    },
    computed: {
        flowConfiguration() {
            return {
                query: () => {
                    return {
                        'currentDirectory': this.currentDirectory,
                        'searchPhrase': this.searchPhrase
                    };
                }
            };
        }
    },
    methods: {
        onFlowUpload() {
            this.$emit('relist');
        }
    }
};
</script>
