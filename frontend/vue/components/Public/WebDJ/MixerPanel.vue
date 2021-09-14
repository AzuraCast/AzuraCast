<template>
    <div class="mixer card">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                <translate key="lang_mixer_title">Mixer</translate>
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-row">
                <div class="flex-shrink-0 pt-1 pr-2">1</div>
                <div class="flex-fill">
                    <input type="range" min="0" max="1" step="0.01" class="custom-range slider" v-model="position">
                </div>
                <div class="flex-shrink-0 pt-1 pl-2">2</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data () {
        return {
            'position': 0.5
        };
    },
    watch: {
        position (val, oldVal) {
            this.$root.$emit('new-mixer-value', val);
        }
    }
};
</script>
