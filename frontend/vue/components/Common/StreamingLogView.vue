<template>
    <b-overlay variant="card" :show="loading">
        <b-form-group label-for="modal_scroll_to_bottom">
            <b-form-checkbox id="modal_scroll_to_bottom" v-model="scrollToBottom">
                <translate key="scroll_to_bottom">Automatically Scroll to Bottom</translate>
            </b-form-checkbox>
        </b-form-group>

        <textarea class="form-control log-viewer" ref="textarea" id="log-view-contents" spellcheck="false"
                  readonly>{{ logs }}</textarea>
    </b-overlay>
</template>

<script>

import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'StreamingLogView',
    components: {BWrappedFormCheckbox},
    props: {
        logUrl: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            loading: false,
            logs: '',
            currentLogPosition: null,
            timeoutUpdateLog: null,
            scrollToBottom: true,
        };
    },
    mounted() {
        this.loading = true;

        this.axios({
            method: 'GET',
            url: this.logUrl
        }).then((resp) => {
            if (resp.data.contents !== '') {
                this.logs = resp.data.contents + "\n";
                this.scrollTextarea();
            } else {
                this.logs = '';
            }

            this.currentLogPosition = resp.data.position;

            if (!resp.data.eof) {
                this.timeoutUpdateLog = setTimeout(this.updateLogs, 2500);
            }
        }).finally(() => {
            this.loading = false;
        });
    },
    beforeDestroy() {
        clearTimeout(this.timeoutUpdateLog);
    },
    methods: {
        updateLogs() {
            this.axios({
                method: 'GET',
                url: this.logUrl,
                params: {
                    position: this.currentLogPosition
                }
            }).then((resp) => {
                if (resp.data.contents !== '') {
                    this.logs = this.logs + resp.data.contents + "\n";
                    this.scrollTextarea();
                }
                this.currentLogPosition = resp.data.position;

                if (!resp.data.eof) {
                    this.timeoutUpdateLog = setTimeout(this.updateLogs, 2500);
                }
            });
        },
        getContents() {
            return this.logs;
        },
        scrollTextarea() {
            if (this.scrollToBottom) {
                this.$nextTick(() => {
                    const textarea = this.$refs.textarea;
                    textarea.scrollTop = textarea.scrollHeight;
                });
            }
        }
    }
};
</script>
