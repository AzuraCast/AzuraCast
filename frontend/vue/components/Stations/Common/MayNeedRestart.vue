<template>

</template>

<script>
export default {
    name: 'StationMayNeedRestart',
    props: {
        restartStatusUrl: String
    },
    methods: {
        mayNeedRestart() {
            this.axios.get(this.restartStatusUrl).then((resp) => {
                if (resp.data.needs_restart) {
                    this.needsRestart();
                }
            });
        },
        needsRestart() {
            document.dispatchEvent(new CustomEvent("station-needs-restart"));
        }
    }
}
;
</script>
