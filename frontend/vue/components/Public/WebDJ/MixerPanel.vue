<template>
    <div class="mixer card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <div class="flex-fill">
                    <h5 class="card-title">
                        <translate key="lang_mixer_title">Mixer</translate>
                    </h5>
                </div>
                <div class="flex-shrink-0 pl-3">
                    <div class="d-flex flex-row align-items-center">
                        <div class="flex-shrink-0">
                            <translate key="lang_playlist_1">Playlist 1</translate>
                        </div>
                        <div class="flex-fill px-2">
                            <input type="range" min="0" max="1" step="0.01" class="custom-range slider"
                                   v-model="position" @click.right.prevent="position = 0.5"
                                   style="width: 200px; height: 10px;">
                        </div>
                        <div class="flex-shrink-0">
                            <translate key="lang_playlist_2">Playlist 2</translate>
                        </div>
                    </div>
                </div>
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
