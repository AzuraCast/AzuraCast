<template>
    <b-overlay variant="card" :show="loading">
        <textarea class="form-control log-viewer" id="log-view-contents" spellcheck="false"
                  readonly>{{ logs }}</textarea>
    </b-overlay>
</template>

<script>

export default {
    name: 'StreamingLogView',
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
    }
};
</script>
