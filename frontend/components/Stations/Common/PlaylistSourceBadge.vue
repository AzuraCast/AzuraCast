<template>
    <template v-if="directName">
        <span
            ref="$badge"
            class="playlist-source-badge badge text-bg-secondary d-inline-flex align-items-center gap-1"
            :aria-label="ariaLabel"
            @mouseenter="onBadgeEnter"
            @mouseleave="scheduleHide()"
        >
            <playlist-source-icon
                v-if="badgeIconSource"
                :source="badgeIconSource"
                :size="IconSize.Small"
            />
            <span class="text-truncate">{{ directName }}</span>
        </span>

        <Teleport to="body">
            <div
                v-if="overlayVisible"
                ref="$overlay"
                class="playlist-source-overlay card position-absolute"
                role="tooltip"
                @mouseenter="clearHideTimer()"
                @mouseleave="scheduleHide()"
            >
                <div class="card-header d-flex align-items-center gap-2 p-2 bg-body-tertiary border-bottom border-2">
                    <playlist-source-icon
                        :source="PlaylistSources.Playlists"
                        :size="IconSize.Small"
                    />
                    <span class="fw-bold flex-grow-1 text-truncate">
                        {{ $gettext('Played via Playlist Group') }}
                    </span>
                </div>

                <ul class="list-group list-group-flush">
                    <li
                        v-for="(entryName, index) in normalizedChain"
                        :key="`${entryName}-${index}`"
                        class="list-group-item d-flex align-items-center gap-2 p-2"
                    >
                        <playlist-source-icon
                            v-if="chainEntrySource(index)"
                            :source="chainEntrySource(index)!"
                            :size="IconSize.Small"
                        />
                        <span class="text-truncate">{{ entryName }}</span>
                    </li>
                </ul>
            </div>
        </Teleport>
    </template>
</template>

<script setup lang="ts">
import { createPopper, Instance } from "@popperjs/core";
import { useTimeoutFn } from "@vueuse/core";
import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    useTemplateRef,
    watch,
} from "vue";
import PlaylistSourceIcon from "~/components/Stations/Common/PlaylistSourceIcon.vue";
import { PlaylistSources } from "~/entities/ApiInterfaces.ts";
import { IconSize } from "~/functions/icons.ts";
import { useTranslate } from "~/vendor/gettext";

const props = defineProps<{
    chain?: string[] | null;
    playlistName?: string | null;
    source?: PlaylistSources | null;
}>();

const { $gettext } = useTranslate();

const normalizedChain = computed<string[]>(() => props.chain ?? []);

const directName = computed<string | null>(
    () =>
        normalizedChain.value[normalizedChain.value.length - 1] ??
        props.playlistName ??
        null,
);

const isGrouped = computed<boolean>(() => normalizedChain.value.length > 1);

const badgeIconSource = computed<PlaylistSources | null>(() =>
    isGrouped.value ? PlaylistSources.Playlists : (props.source ?? null),
);

const chainEntrySource = (index: number): PlaylistSources | null =>
    index < normalizedChain.value.length - 1
        ? PlaylistSources.Playlists
        : (props.source ?? null);

const ariaLabel = computed<string>(() =>
    isGrouped.value
        ? normalizedChain.value.join(" → ")
        : (directName.value ?? ""),
);

const overlayVisible = ref<boolean>(false);

const { start: scheduleHide, stop: clearHideTimer } = useTimeoutFn(
    () => {
        overlayVisible.value = false;
    },
    150,
    { immediate: false },
);

const onBadgeEnter = () => {
    if (!isGrouped.value) {
        return;
    }

    clearHideTimer();
    overlayVisible.value = true;
};

let popper: Instance | null = null;

const destroyPopper = () => {
    popper?.destroy();
    popper = null;
};

onBeforeUnmount(destroyPopper);

const $badge = useTemplateRef<HTMLElement>("$badge");
const $overlay = useTemplateRef<HTMLElement>("$overlay");

watch(overlayVisible, async (visible) => {
    destroyPopper();

    if (!visible || !$badge.value) {
        return;
    }

    await nextTick();

    if ($overlay.value) {
        popper = createPopper($badge.value, $overlay.value, {
            placement: "top",
            modifiers: [
                {
                    name: "flip",
                    options: {
                        fallbackPlacements: ["bottom", "right", "left"],
                    },
                },
                { name: "preventOverflow", options: { padding: 8 } },
            ],
        });
    }
});
</script>

<style lang="scss" scoped>
.playlist-source-badge {
    max-width: 100%;
}

.playlist-source-overlay {
    z-index: 1070;
    min-width: 12rem;
    max-width: 24rem;
}
</style>
