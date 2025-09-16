<template>
    <audio
        v-if="isPlaying"
        ref="$audio"
        :title="current.title ?? undefined"
    />
</template>

<script setup lang="ts">
import Hls from "hls.js";
import {nextTick, onMounted, onScopeDispose, ref, useTemplateRef, watch} from "vue";
import {usePlayerStore} from "~/functions/usePlayerStore.ts";
import {storeToRefs} from "pinia";
import {isString} from "es-toolkit/compat";

const playerStore = usePlayerStore();
const {logVolume, isMuted, isPlaying, current, duration, progress} = storeToRefs(playerStore);
const {stop: storeStop, setIsPlaying, setPlayPosition} = playerStore;

const $audio = useTemplateRef('$audio');

const hls = ref<Hls | null>(null);
const bc = ref<BroadcastChannel | null>(null);

watch(logVolume, (newVol) => {
    if ($audio.value !== null) {
        $audio.value.volume = newVol;
    }
});

watch(isMuted, (newMuted) => {
    if ($audio.value !== null) {
        $audio.value.muted = newMuted;
    }
});

const stop = () => {
    if ($audio.value !== null) {
        $audio.value.pause();
        $audio.value.src = '';
    }

    if (hls.value !== null) {
        hls.value.destroy();
        hls.value = null;
    }

    setIsPlaying(false);
};

const play = () => {
    if (isPlaying.value) {
        stop();
        void nextTick(() => {
            play();
        });
        return;
    }

    isPlaying.value = true;

    void nextTick(() => {
        if (!$audio.value) {
            return;
        }

        // Handle audio errors.
        $audio.value.onerror = (e: string | Event) => {
            if (isString(e)) {
                return;
            }

            const eventTarget = e.target as HTMLAudioElement;

            if (eventTarget.error?.code === MediaError.MEDIA_ERR_NETWORK && $audio.value?.src) {
                console.log('Network interrupted stream. Automatically reconnecting shortly...');
                setTimeout(() => {
                    play();
                }, 5000);
            }
        };

        $audio.value.onended = () => {
            stop();
        };

        $audio.value.ontimeupdate = () => {
            const audioDuration = $audio.value?.duration ?? 0;

            setPlayPosition(
                (audioDuration !== Infinity && !isNaN(audioDuration)) ? audioDuration : 0,
                $audio.value?.currentTime ?? 0
            );
        };

        $audio.value.volume = logVolume.value;
        $audio.value.muted = isMuted.value;

        if (current.value.url !== null) {
            if (current.value.isHls) {
                // HLS playback support
                if (Hls.isSupported()) {
                    hls.value = new Hls();
                    hls.value.loadSource(current.value.url);
                    hls.value.attachMedia($audio.value);
                } else if ($audio.value.canPlayType('application/vnd.apple.mpegurl')) {
                    $audio.value.src = current.value.url;
                } else {
                    console.log('Your browser does not support HLS.');
                }
            } else {
                // Standard streams
                $audio.value.src = current.value.url;

                // Firefox caches the downloaded stream, this causes playback issues.
                // Giving the browser a new url on each start bypasses the old cache/buffer
                if (navigator.userAgent.includes("Firefox")) {
                    $audio.value.src += "?refresh=" + Date.now();
                }
            }
        }

        $audio.value.load();
        $audio.value.play();

        if (bc.value) {
            bc.value.postMessage('played');
        }
    });
};

watch(progress, (newProgress) => {
    if (newProgress.isSeek && $audio.value !== null) {
        $audio.value.currentTime = (newProgress.position / 100) * duration.value;
    }
});

watch(current, (newCurrent) => {
    if (newCurrent.url === null) {
        stop();
    } else {
        play();
    }
});

onMounted(() => {
    // Allow pausing from the mobile metadata update.
    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('pause', () => {
            storeStop();
        });
    }

    if ('BroadcastChannel' in window) {
        bc.value = new BroadcastChannel('audio_player');
        bc.value.addEventListener('message', () => {
            storeStop();
        }, {passive: true});
    }
});

onScopeDispose(() => {
    if (bc.value) {
        bc.value.close()
    }
});
</script>
