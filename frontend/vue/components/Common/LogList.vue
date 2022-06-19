<template>
    <div class="list-group list-group-flush">
        <a v-for="log in logs" :key="log.key" class="list-group-item list-group-item-action log-item"
           href="#" @click.prevent="viewLog(log.links.self)">
            <span class="log-name">{{ log.name }}</span><br>
            <small class="text-secondary">{{ log.path }}</small>
        </a>
    </div>
</template>

<script>
export default {
    name: 'LogList',
    emits: ['view'],
    props: {
        url: String,
    },
    data() {
        return {
            loading: true,
            logs: []
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.loading = true;
            this.$wrapWithLoading(
                this.axios.get(this.url)
            ).then((resp) => {
                this.logs = resp.data.logs;
            }).finally(() => {
                this.loading = false;
            });
        },
        viewLog(url) {
            this.$emit('view', url);
        }
    }
}
</script>
