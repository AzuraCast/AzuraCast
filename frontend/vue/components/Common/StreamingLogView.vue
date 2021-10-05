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
            this.logs = resp.data.contents + "\n";
            this.currentLogPosition = resp.data.position;

            if (!resp.data.eof) {
                this.timeoutUpdateLog = setTimeout(this.updateLogs, 15000);
            }
        }).finally(() => {
            this.loading = false;
        });
    },
    methods: {
        updateLogs() {
            this.axios({
                method: 'GET',
                url: this.logUrl
            }).then((resp) => {
                if (resp.data.contents !== '') {
                    this.logs = this.logs + resp.data.contents + "\n";
                }
                this.currentLogPosition = resp.data.position;

                if (!resp.data.eof) {
                    this.timeoutUpdateLog = setTimeout(this.updateLogs, 15000);
                }
            });
        },
        getContents() {
            return this.logs;
        },
    }
};
</script>
