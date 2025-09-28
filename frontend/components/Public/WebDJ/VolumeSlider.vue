<template>
    <div class="d-flex flex-row align-items-center">
        <div class="flex-shrink-0 me-2">
            <mute-button
                class="p-0"
                :volume="volume"
                :is-muted="isMuted"
                @toggle-mute="toggleMute"
            />
        </div>
        <div class="flex-fill px-2">
            <input
                v-model.number="volume"
                type="range"
                min="0"
                max="100"
                class="form-range slider"
                style="height: 10px; width: 125px;"
                @click.right.prevent="reset"
            >
        </div>
    </div>
</template>

<script setup lang="ts">
import {computed, onMounted, ref} from "vue";
import MuteButton from "~/components/Common/Audio/MuteButton.vue";

const defaultVolume = 75;

const volume = defineModel<number>({
    default: defaultVolume
});

const initial = ref(defaultVolume);
const preMute = ref(defaultVolume);

onMounted(() => {
    initial.value = volume.value;
    preMute.value = volume.value;
});

const isMuted = computed(() => {
    return volume.value === 0;
});

const toggleMute = () => {
    if (isMuted.value) {
        volume.value = preMute.value;
    } else {
        preMute.value = volume.value;
        volume.value = 0;
    }
}

const reset = () => {
    volume.value = initial.value;
}
</script>
