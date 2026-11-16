<template>
    <div class="mixer card">
        <div class="card-header text-bg-primary">
            <div class="d-flex align-items-center">
                <div class="flex-fill">
                    <h5 class="card-title">
                        {{ $gettext('Mixer') }}
                    </h5>
                </div>
                <div class="flex-shrink-0 ps-3">
                    <div class="d-flex flex-row align-items-center">
                        <div class="flex-shrink-0">
                            {{ $gettext('Playlist 1') }}
                        </div>
                        <div class="flex-fill px-2">
                            <input
                                v-model.number="crossfadeValue"
                                type="range"
                                min="0"
                                max="100"
                                step="1"
                                class="form-range slider"
                                style="width: 200px; height: 10px;"
                                @click.right.prevent="crossfadeValue = 50"
                            >
                        </div>
                        <div class="flex-shrink-0">
                            {{ $gettext('Playlist 2') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useInjectWebcasterMixer } from "~/components/Public/WebDJ/useWebcasterMixer.ts";

const { setCrossfade } = useInjectWebcasterMixer();

const crossfadeValue = ref<number>(50);

watch(crossfadeValue, (newValue) => {
    setCrossfade(newValue / 100);
});
</script>
