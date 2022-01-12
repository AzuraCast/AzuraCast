<template>
    <div class="d-flex flex-row align-items-center">
        <div class="flex-shrink-0">
            <icon icon="volume_mute"></icon>
        </div>
        <div class="flex-fill px-2">
            <input type="range" min="0" max="100" class="custom-range slider"
                   v-model.number="volume" @click.right.prevent="reset" style="height: 10px; width: 100px;">
        </div>
        <div class="flex-shrink-0">
            <icon icon="volume_up"></icon>
        </div>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";

export default {
    components: {Icon},
    name: "VolumeSlider",
    emits: "input",
    props: ['value'],
    data() {
        return {
            initial: 75
        }
    },
    mounted() {
        this.initial = this.value;
    },
    computed: {
        volume: {
            get() {
                return this.value
            },
            set(newValue) {
                this.$emit('input', newValue);
            }
        }
    },
    methods: {
        reset() {
            this.volume = this.initial;
        }
    }
}
</script>
