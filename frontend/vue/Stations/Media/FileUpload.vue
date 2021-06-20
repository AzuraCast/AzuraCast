<template>
    <flow-upload :target-url="uploadUrl" :flow-configuration="flowConfiguration"
                 :valid-mime-types="validMimeTypes" allow-multiple
                 @complete="onFlowUpload" @error="onFlowUpload">
    </flow-upload>
</template>

<script>
import FlowUpload from '../../Common/FlowUpload';

export default {
    name: 'FileUpload',
    components: { FlowUpload },
    props: {
        uploadUrl: String,
        currentDirectory: String,
        searchPhrase: String,
        validMimeTypes: {
            type: Array,
            default () {
                return ['audio/*'];
            }
        }
    },
    data () {
        return {
            flow: null,
            files: []
        };
    },
    computed: {
        flowConfiguration () {
            return {
                testChunks: true,
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
        onFlowUpload () {
            this.$emit('relist');
        }
    }
};
</script>
