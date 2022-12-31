<template>
    <div class="mixer card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <div class="flex-fill">
                    <h5 class="card-title">
                        {{ $gettext('Mixer') }}
                    </h5>
                </div>
                <div class="flex-shrink-0 pl-3">
                    <div class="d-flex flex-row align-items-center">
                        <div class="flex-shrink-0">
                            {{ $gettext('Playlist 1') }}
                        </div>
                        <div class="flex-fill px-2">
                            <input
                                v-model="mixerValue"
                                type="range"
                                min="0"
                                max="1"
                                step="0.01"
                                class="custom-range slider"
                                style="width: 200px; height: 10px;"
                                @click.right.prevent="mixerValue = 0.5"
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

<script setup>
import {useMixerValue} from "~/components/Public/WebDJ/useMixerValue";

const mixerValue = useMixerValue();
</script>
