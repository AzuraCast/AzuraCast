<template>
    <Teleport to="body">
        <div
            v-if="visible && details"
            ref="$overlay"
            class="schedule-event-overlay card position-absolute"
            role="tooltip"
            @mouseenter="emit('mouseenter')"
            @mouseleave="emit('mouseleave')"
        >
            <div class="card-header d-flex align-items-center gap-2 p-2 bg-body-tertiary border-bottom border-2">
                <span
                    v-if="details.type === 'streamer'"
                    class="schedule-streamer-art rounded-circle flex-shrink-0 d-inline-flex align-items-center justify-content-center overflow-hidden"
                >
                    <img
                        v-if="details.has_custom_art"
                        :src="details.art ?? undefined"
                        class="w-100 h-100 object-fit-cover"
                        alt="Streamer Artwork"
                    >
                    <icon-bi-mic-fill v-else />
                </span>
                <playlist-source-icon
                    v-else-if="details.source"
                    :source="details.source"
                />

                <span class="fw-bold flex-grow-1 text-truncate">{{ details.name }}</span>

                <span
                    v-if="headerCount !== null"
                    class="badge text-bg-light rounded-pill shadow-none"
                >{{ headerCount }}</span>
            </div>

            <div
                v-if="details.type === 'streamer'"
                class="card-body py-2"
            >
                <div
                    v-if="details.streamer_username"
                    class="text-muted small"
                >
                    {{ details.streamer_username }}
                </div>
                <div
                    v-if="details.comments"
                    class="mt-1"
                >
                    {{ details.comments }}
                </div>
            </div>

            <template v-else>
                <div class="card-body p-2 d-flex flex-column gap-2">
                    <div
                        v-if="details.source === PlaylistSources.Songs"
                        class="d-flex align-items-center gap-2"
                    >
                        <span class="text-muted">{{ formatLength(details.total_length ?? 0) }}</span>
                    </div>

                    <div
                        v-else-if="details.source === PlaylistSources.RemoteUrl"
                        class="d-flex flex-column gap-1"
                    >
                        <div
                            v-if="details.remote_url"
                            class="text-truncate text-muted small"
                            :title="details.remote_url"
                        >
                            {{ details.remote_url }}
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                        <span
                            v-if="showRotation"
                            class="badge text-bg-secondary"
                        >
                            {{ getOrderLabel(details.order) }}
                        </span>

                        <span
                            v-if="showRotation"
                            class="badge text-bg-secondary"
                        >
                            {{ rotationLabel }}
                        </span>

                        <span
                            v-if="details.source === PlaylistSources.RemoteUrl"
                            class="badge text-bg-secondary"
                        >
                            {{ remoteTypeLabel }}
                        </span>

                        <span
                            v-if="details.is_jingle"
                            class="badge text-bg-info"
                        >
                            {{ $gettext('Jingle Mode') }}
                        </span>

                        <span
                            v-if="details.include_in_on_demand"
                            class="badge text-bg-info"
                        >
                            {{ $gettext('On-Demand') }}
                        </span>

                        <span
                            v-if="details.avoid_duplicates"
                            class="badge text-bg-info"
                        >
                            {{ $gettext('Avoid Duplicates') }}
                        </span>
                    </div>
                </div>

                <ul
                    v-if="details.source === PlaylistSources.Playlists && members.length > 0"
                    class="list-group list-group-flush overflow-y-auto border-top"
                >
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="list-group-item d-flex flex-column gap-2 p-2"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <playlist-source-icon
                                :size="IconSize.Small"
                                :source="member.source"
                            />

                            <span class="flex-grow-1 text-truncate">{{ member.name }}</span>

                            <span
                                v-if="member.count !== null"
                                class="badge bg-primary rounded-pill"
                            >
                                {{ member.count }}
                            </span>
                        </div>
                        <div
                            v-if="memberHasBadges(member)"
                            class="d-flex flex-wrap gap-1"
                        >
                            <span
                                v-if="memberHasOrder(member)"
                                class="badge text-bg-secondary"
                            >
                                {{ getOrderLabel(member.order) }}
                            </span>

                            <span
                                v-if="member.play_full_cycle || member.consecutive_plays > 0"
                                class="badge text-bg-secondary d-inline-flex align-items-center gap-1 ms-auto"
                            >
                                <icon-bi-arrow-repeat class="sm" />
                                {{
                                    member.play_full_cycle
                                        ? $gettext('Plays fully')
                                        : $gettext('Plays %{count}', {count: member.consecutive_plays})
                                }}
                            </span>
                        </div>
                    </li>
                </ul>
            </template>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { createPopper, Instance } from "@popperjs/core";
import {
    computed,
    nextTick,
    onBeforeUnmount,
    useTemplateRef,
    watch,
} from "vue";
import PlaylistSourceIcon from "~/components/Stations/Common/PlaylistSourceIcon.vue";
import {
    PlaylistOrders,
    PlaylistRemoteTypes,
    PlaylistSources,
    PlaylistTypes,
} from "~/entities/ApiInterfaces.ts";
import {
    ScheduleEventDetails,
    ScheduleGroupMember,
} from "~/entities/StationSchedule.ts";
import { IconSize } from "~/functions/icons.ts";
import { useFormatLength } from "~/functions/useFormatLength.ts";
import { useTranslate } from "~/vendor/gettext";
import IconBiArrowRepeat from "~icons/bi/arrow-repeat";
import IconBiMicFill from "~icons/bi/mic-fill";

const props = defineProps<{
    visible: boolean;
    referenceElement: HTMLElement | null;
    details: ScheduleEventDetails | null;
}>();

const emit = defineEmits<{
    mouseenter: [];
    mouseleave: [];
}>();

const { $gettext } = useTranslate();
const formatLength = useFormatLength();

const playlistDetails = computed(() => {
    return props.details?.type === "playlist" ? props.details : null;
});

const members = computed<ScheduleGroupMember[]>(
    () => playlistDetails.value?.members ?? [],
);

const headerCount = computed<number | null>(() => {
    const details = playlistDetails.value;
    if (!details) {
        return null;
    }

    if (details.source === PlaylistSources.Songs) {
        return details.num_songs ?? 0;
    }

    if (details.source === PlaylistSources.Playlists) {
        return members.value.length;
    }

    return null;
});

const showRotation = computed<boolean>(
    () =>
        playlistDetails.value?.source === PlaylistSources.Songs ||
        playlistDetails.value?.source === PlaylistSources.Playlists,
);

const getOrderLabel = (order?: string): string => {
    switch (order) {
        case PlaylistOrders.Shuffle:
            return $gettext("Shuffle");
        case PlaylistOrders.Sequential:
            return $gettext("Sequential");
        case PlaylistOrders.Random:
            return $gettext("Random");
        default:
            return "";
    }
};

const memberHasOrder = (member: ScheduleGroupMember): boolean =>
    member.source === PlaylistSources.Songs ||
    member.source === PlaylistSources.Playlists;

const memberHasBadges = (member: ScheduleGroupMember): boolean =>
    memberHasOrder(member) ||
    member.play_full_cycle ||
    member.consecutive_plays > 0;

const rotationLabel = computed<string>(() => {
    const details = playlistDetails.value;

    switch (details?.playlist_type) {
        case PlaylistTypes.Standard:
            return $gettext("General Rotation (%{weight})", {
                weight: details.weight ?? 0,
            });

        case PlaylistTypes.OncePerXSongs:
            return $gettext("Once per %{songs} Songs", {
                songs: details.play_per_songs ?? 0,
            });

        case PlaylistTypes.OncePerXMinutes:
            return $gettext("Once per %{minutes} Minutes", {
                minutes: details.play_per_minutes ?? 0,
            });

        case PlaylistTypes.OncePerHour:
            return $gettext("Once per Hour (at %{minute})", {
                minute: details.play_per_hour_minute ?? 0,
            });

        default:
            return $gettext("Custom");
    }
});

const remoteTypeLabel = computed<string>(() => {
    switch (playlistDetails.value?.remote_type) {
        case PlaylistRemoteTypes.Stream:
            return $gettext("Remote Stream");
        case PlaylistRemoteTypes.Playlist:
            return $gettext("Remote Playlist");
        default:
            return $gettext("Remote URL");
    }
});

const $overlay = useTemplateRef<HTMLElement>("$overlay");

let popper: Instance | null = null;

const destroyPopper = () => {
    popper?.destroy();
    popper = null;
};

onBeforeUnmount(destroyPopper);

watch(
    () => [props.visible, props.referenceElement] as const,
    async ([visible, referenceElement]) => {
        destroyPopper();

        if (!visible || !referenceElement || !props.details) {
            return;
        }

        await nextTick();

        if ($overlay.value) {
            popper = createPopper(referenceElement, $overlay.value, {
                placement: "right",
                modifiers: [
                    {
                        name: "flip",
                        options: {
                            fallbackPlacements: ["top", "left", "bottom"],
                        },
                    },
                    { name: "preventOverflow", options: { padding: 8 } },
                ],
            });
        }
    },
);
</script>

<style lang="scss" scoped>
@import "~/scss/_variables.scss";

.schedule-event-overlay {
    z-index: 1070;
    min-width: 14rem;
    max-width: 28rem;

    .schedule-streamer-art {
        width: $icon-size-lg;
        height: $icon-size-lg;
    }

    > .list-group {
        max-height: 20rem;
    }
}
</style>
