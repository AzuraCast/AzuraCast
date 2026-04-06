<template>
    <tab
        ref="$tab"
        id="playlist_grouping"
        :label="$gettext('Playlist Grouping')"
    >
        <div class="card-body-flush">
            <div
                v-if="loading"
                class="p-5 text-center"
            >
                <div class="spinner-border" />
            </div>
            <div
                v-else
                class="row gx-1 pt-3 overflow-hidden"
            >
                <div class="col-6">
                    <h4 class="bg-primary text-bg-primary text-center p-3 mb-0 shadow">
                        {{ $gettext('Playlists') }}
                    </h4>

                    <nav
                        v-if="playlistBreadcrumbs.length"
                        style="--bs-breadcrumb-divider: '>';"
                        aria-label="breadcrumb"
                        class="border border-3 border-top-0 border-primary p-3 overflow-scroll"
                    >
                        <ol class="breadcrumb flex-nowrap m-0">
                            <li class="breadcrumb-item">
                                <a
                                    href="#"
                                    @click.prevent="navigateFromBreadcrumb()"
                                >
                                    <icon-ic-home />
                                </a>
                            </li>

                            <template
                                v-for="(breadcrumb, index) in playlistBreadcrumbs"
                                :key="breadcrumb.id"
                            >
                                <template v-if="index < (playlistBreadcrumbs.length - 1)">
                                    <li class="breadcrumb-item text-nowrap">
                                        <a
                                            href="#"
                                            class="text-nowrap"
                                            @click.prevent="navigateFromBreadcrumb(index + 1)"
                                        >
                                            {{ breadcrumb.name }}
                                        </a>
                                    </li>
                                </template>
                                <template v-else>
                                    <li
                                        class="breadcrumb-item text-nowrap active"
                                        aria-current="page"
                                    >
                                        <span class="text-nowrap">{{ breadcrumb.name }}</span>
                                    </li>
                                </template>
                            </template>
                        </ol>
                    </nav>

                    <ul
                        ref="$playlistList"
                        class="list-group list-group-flush h-100 shadow"
                    >
                        <li
                            v-if="currentPlaylists.length === 0"
                            class="not-assignable"
                        >
                            <div class="p-5 text-center fs-5">
                                {{ $gettext('No playlists available') }}
                            </div>
                        </li>
                        <li
                            v-for="(item, index) in currentPlaylists"
                            :key="`${item.id}-${index}`"
                            class="list-group-item p-0"
                            :class="{active: isSelected(item), 'not-assignable': !isAssignable(item)}"
                        >
                            <label
                                class="playlist-selection-item d-block w-100 p-3"
                                :class="{selectable: isSelectable(item)}"
                            >
                                <input
                                    class="form-check-input d-none"
                                    name="selected-playlist"
                                    type="radio"
                                    :value="item"
                                    :disabled="!isSelectable(item)"
                                    v-model="selectedPlaylist"
                                />

                                <div class="d-flex">
                                    <div class="d-flex flex-column flex-grow-1 min-w-0">
                                        <span class="pr-2 fs-5">{{ item.name }}</span>

                                        <div
                                            class="text-truncate text-muted mb-3"
                                            :title="item.description"
                                        >
                                            {{ item.description }}
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge text-bg-secondary">
                                                <div class="d-flex align-items-center">
                                                    <template v-if="item.source === 'songs'">
                                                        <icon-ic-library-music />
                                                        <div class="ps-2">{{ $gettext('Song-based') }}</div>
                                                    </template>
                                                    <template v-else-if="item.source === 'playlists'">
                                                        <icon-ic-queue-music />
                                                        <div class="ps-2">{{ $gettext('Playlist Group') }}</div>
                                                    </template>
                                                    <template v-else>
                                                        <icon-ic-public />
                                                        <div class="ps-2">{{ $gettext('Remote URL') }}</div>
                                                    </template>
                                                </div>
                                            </span>

                                            <span
                                                v-if="item.source === 'songs'"
                                                class="badge bg-primary rounded-pill"
                                                v-text="item.num_songs"
                                            />
                                            <span
                                                v-else-if="item.source === 'playlists'"
                                                class="badge bg-primary rounded-pill"
                                                v-text="item.playlists.length"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-if="hasButtons(item)"
                                        class="btn-group-vertical ms-3 align-self-center"
                                    >
                                        <button
                                            v-if="isAssignable(item)"
                                            type="button"
                                            :title="$gettext('Add to selected playlist group')"
                                            class="btn btn-primary"
                                            :disabled="saving"
                                            @click="doAssign(item)"
                                        >
                                            <icon-ic-drive-file-move />
                                        </button>

                                        <button
                                            v-if="item.source === 'playlists'"
                                            type="button"
                                            :title="$gettext('Enter playlist group')"
                                            class="btn btn-secondary"
                                            @click="enterPlaylistGroup(item)"
                                        >
                                            <icon-bi-folder />
                                        </button>
                                    </div>
                                </div>
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="col-6">
                    <h4 class="bg-primary text-bg-primary text-center p-3 mb-0 shadow">
                        {{ $gettext('Playlist Contents') }}
                    </h4>
                    <div
                        v-if="selectedPlaylist !== undefined"
                        class="selected-playlist-details border border-3 border-top-0 border-primary p-3 shadow-lg"
                    >
                        <div class="d-flex flex-grow-1 justify-content-between align-items-start">
                            <span class="pr-2 fs-5">{{ selectedPlaylist.name }}</span>

                            <span
                                v-if="selectedPlaylist.source === 'songs'"
                                class="badge bg-primary rounded-pill"
                                v-text="selectedPlaylist.num_songs"
                            />
                            <span
                                v-else-if="selectedPlaylist.source === 'playlists'"
                                class="badge bg-primary rounded-pill"
                                v-text="selectedPlaylist.playlists.length"
                            />
                        </div>

                        <div
                            class="text-truncate text-muted mb-3"
                            :title="selectedPlaylist.description"
                        >
                            {{ selectedPlaylist.description }}
                        </div>

                        <div class="badges">
                            <span class="badge text-bg-primary">
                                <template v-if="selectedPlaylist.source === 'songs'">
                                    {{ $gettext('Song-based') }}
                                </template>
                                <template v-else-if="selectedPlaylist.source === 'playlists'">
                                    {{ $gettext('Playlist Group') }}
                                </template>
                                <template v-else>
                                    {{ $gettext('Remote URL') }}
                                </template>
                            </span>
                            <span
                                v-if="!selectedPlaylist.is_enabled"
                                class="badge text-bg-danger"
                            >
                                {{ $gettext('Disabled') }}
                            </span>
                            <span
                                v-if="selectedPlaylist.is_jingle"
                                class="badge text-bg-primary"
                            >
                                {{ $gettext('Jingle Mode') }}
                            </span>
                            <span
                                v-if="selectedPlaylist.order === 'sequential'"
                                class="badge text-bg-info"
                            >
                                {{ $gettext('Sequential') }}
                            </span>
                            <span
                                v-if="selectedPlaylist.schedule_items.length > 0"
                                class="badge text-bg-info"
                            >
                                {{ $gettext('Scheduled') }}
                            </span>
                            <span class="badge text-bg-secondary">
                                <template v-if="selectedPlaylist.type === 'default'">
                                    {{ $gettext('General Rotation') }} ({{ selectedPlaylist.weight }})<br>
                                </template>
                                <template v-else-if="selectedPlaylist.type === 'once_per_x_songs'">
                                    {{
                                        $gettext(
                                            'Once per %{songs} Songs',
                                            {songs: selectedPlaylist.play_per_songs}
                                        )
                                    }}
                                </template>
                                <template v-else-if="selectedPlaylist.type === 'once_per_x_minutes'">
                                    {{
                                        $gettext(
                                            'Once per %{minutes} Minutes',
                                            {minutes: selectedPlaylist.play_per_minutes}
                                        )
                                    }}
                                </template>
                                <template v-else-if="selectedPlaylist.type === 'once_per_hour'">
                                    {{
                                        $gettext(
                                            'Once per Hour (at %{minute})',
                                            {minute: selectedPlaylist.play_per_hour_minute}
                                        )
                                    }}
                                </template>
                                <template v-else>
                                    {{ $gettext('Custom') }}
                                </template>
                            </span>
                            <span
                                v-if="selectedPlaylist.include_in_on_demand"
                                class="badge text-bg-info"
                            >
                                {{ $gettext('On-Demand') }}
                            </span>
                        </div>
                    </div>

                    <ul
                        ref="$playlistContents"
                        class="list-group list-group-flush h-100 shadow"
                    >
                        <li
                            v-if="selectedPlaylist === undefined"
                            class="no-drag"
                        >
                            <div class="p-5 text-center fs-5">
                                {{ $gettext('No playlist selected') }}
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'songs' && selectedPlaylist.num_songs === 0"
                            class="no-drag"
                        >
                            <div class="p-5 text-center fs-5">
                                {{ $gettext('No songs available') }}
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'playlists' && selectedPlaylist.playlists.length === 0"
                            class="no-drag"
                        >
                            <div class="p-5 text-center fs-5">
                                {{ $gettext('No playlists assigned') }}
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'songs'"
                            class="list-group-item no-drag"
                        >
                            <div class="p-3 text-center text-muted">
                                {{ $gettext('%{count} songs in this playlist.', {count: selectedPlaylist.num_songs}) }}
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'playlists'"
                            v-for="(member, index) in playlistMembers"
                            :key="`${selectedPlaylist.id}-${member.id}-${index}`"
                            class="list-group-item"
                        >
                            <div class="d-flex">
                                <div class="d-flex flex-column flex-grow-1 min-w-0">
                                    <div class="d-flex flex-grow-1 justify-content-between align-items-start mb-2">
                                        <span class="flex-grow-1 pr-2 fs-5">{{ member.name }}</span>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            :title="$gettext('Remove from group')"
                                            :disabled="saving"
                                            @click="doRemove(index)"
                                        >
                                            <icon-ic-delete />
                                        </button>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge text-bg-secondary">
                                                <div class="d-flex align-items-center">
                                                    <template v-if="member.source === 'songs'">
                                                        <icon-ic-library-music />
                                                        <div class="ps-2">{{ $gettext('Song-based') }}</div>
                                                    </template>
                                                    <template v-else-if="member.source === 'playlists'">
                                                        <icon-ic-queue-music />
                                                        <div class="ps-2">{{ $gettext('Playlist Group') }}</div>
                                                    </template>
                                                    <template v-else>
                                                        <icon-ic-public />
                                                        <div class="ps-2">{{ $gettext('Remote URL') }}</div>
                                                    </template>
                                                </div>
                                            </span>

                                            <span
                                                v-if="member.source === 'songs'"
                                                class="badge bg-primary rounded-pill"
                                                v-text="member.num_songs"
                                            />
                                            <span
                                                v-else-if="member.source === 'playlists'"
                                                class="badge bg-primary rounded-pill"
                                                v-text="member.playlists.length"
                                            />
                                        </div>

                                        <div class="btn-group btn-group-sm">
                                            <button
                                                v-if="index + 1 < playlistMembers.length"
                                                type="button"
                                                class="btn btn-secondary"
                                                :title="$gettext('Move to Bottom')"
                                                :disabled="saving"
                                                @click.prevent="doMoveToBottom(index)"
                                            >
                                                <icon-bi-chevron-bar-down />
                                            </button>
                                            <button
                                                v-if="index + 1 < playlistMembers.length"
                                                type="button"
                                                class="btn btn-primary"
                                                :title="$gettext('Move Down')"
                                                :disabled="saving"
                                                @click.prevent="doMoveDown(index)"
                                            >
                                                <icon-bi-chevron-down />
                                            </button>
                                            <button
                                                v-if="index > 0"
                                                type="button"
                                                class="btn btn-primary"
                                                :title="$gettext('Move Up')"
                                                :disabled="saving"
                                                @click.prevent="doMoveUp(index)"
                                            >
                                                <icon-bi-chevron-up />
                                            </button>
                                            <button
                                                v-if="index > 0"
                                                type="button"
                                                class="btn btn-secondary"
                                                :title="$gettext('Move to Top')"
                                                :disabled="saving"
                                                @click.prevent="doMoveToTop(index)"
                                            >
                                                <icon-bi-chevron-bar-up />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </tab>
</template>

<script setup lang="ts">
import Tab from "~/components/Common/Tab.vue";
import IconIcHome from "~icons/ic/baseline-home";
import IconIcLibraryMusic from "~icons/ic/baseline-library-music";
import IconIcQueueMusic from "~icons/ic/baseline-queue-music";
import IconIcPublic from "~icons/ic/baseline-public";
import IconIcDriveFileMove from "~icons/ic/baseline-drive-file-move";
import IconIcDelete from "~icons/ic/baseline-delete";
import IconBiFolder from "~icons/bi/folder";
import IconBiChevronBarDown from "~icons/bi/chevron-bar-down";
import IconBiChevronBarUp from "~icons/bi/chevron-bar-up";
import IconBiChevronDown from "~icons/bi/chevron-down";
import IconBiChevronUp from "~icons/bi/chevron-up";
import {ref, useTemplateRef, watch} from "vue";
import {useDraggable} from "vue-draggable-plus";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";

// @TODO: The types are currently a inline placeholder implementation that should get replaced by generated ones
// from the frontend/entities/ApiInterfaces.ts & a new frontend/entities/StationPlaylist.ts later on

export type PlaylistMember = {
    id: number,
    name: string,
    weight: number,
    source: string,
    num_songs: number,
    playlists: PlaylistMember[],
};

export type Playlist = {
    id: number,
    name: string,
    description: string,
    source: string,
    type: string,
    is_jingle: boolean,
    order: string,
    weight: number,
    play_per_songs: number,
    play_per_minutes: number,
    play_per_hour_minute: number,
    include_in_on_demand: boolean,
    schedule_items: unknown[],
    is_enabled: boolean,
    num_songs: number,
    playlists: PlaylistMember[],
    links: {
        self: string,
        members?: string,
    },
};

export type PlaylistBreadcrumb = {
    id: number,
    name: string,
};

const props = defineProps<{
    listUrl: string
}>();

const $tab = useTemplateRef<InstanceType<typeof Tab>>('$tab');
const $playlistList = useTemplateRef<HTMLUListElement>('$playlistList');
const $playlistContents = useTemplateRef<HTMLUListElement>('$playlistContents');

watch(
    () => $tab.value?.isActive,
    (isActive) => {
        if (isActive) {
            void loadPlaylists();
        }
    }
);

const {$gettext} = useTranslate();
const {axios} = useAxios();
const {notifySuccess, notifyError} = useNotify();

const loading = ref<boolean>(true);
const saving = ref<boolean>(false);
const playlists = ref<Playlist[]>([]);
const currentPlaylists = ref<Playlist[]>([]);
const playlistBreadcrumbs = ref<PlaylistBreadcrumb[]>([]);
const selectedPlaylist = ref<Playlist | undefined>(undefined);
const playlistMembers = ref<PlaylistMember[]>([]);

watch(selectedPlaylist, (playlist) => {
    playlistMembers.value = playlist?.source === 'playlists'
        ? [...playlist.playlists]
        : [];
});

watch($playlistList, (element) => {
    if (element === null) {
        return;
    }

    useDraggable($playlistList, currentPlaylists, {
        group: {
            name: 'playlist-grouping',
            pull: 'clone',
            put: false,
        },
        filter: '.not-assignable',
        sort: false,
        clone: (playlist: Playlist) => ({
            id: playlist.id,
            name: playlist.name,
            weight: 0,
            source: playlist.source,
            num_songs: playlist.num_songs,
            playlists: playlist.playlists,
        }) as Playlist,
    });
});

watch($playlistContents, (element) => {
    if (element === null) {
        return
    }

    useDraggable($playlistContents, playlistMembers, {
        group: {
            name: 'playlist-grouping',
            put: true,
        },
        filter: '.no-drag',
        onEnd() {
            void saveMembersForSelected(playlistMembers.value);
        },
        onAdd() {
            void saveMembersForSelected(playlistMembers.value);
        }
    });
});

/**
 * Since all playlists that are assigned to a StationPlaylist are returned as
 * their StationPlaylistGroup (id, name, weight) we need to enrich those to
 * have their full StationPlaylist forms available for easier handling.
 */
const buildTree = (raw: Playlist[]): Playlist[] => {
    const map = new Map<number, Playlist>(raw.map((playlist) => [playlist.id, playlist]));

    for (const playlist of raw) {
        if (playlist.source !== 'playlists' || !Array.isArray(playlist.playlists)) {
            continue;
        }

        playlist.playlists = playlist.playlists
            .map((member) => {
                const fullPlaylist = map.get(member.id);
                return fullPlaylist
                    ? {
                        ...fullPlaylist,
                        weight: member.weight,
                    }
                    : {
                        ...member,
                        description: '',
                        type: 'default',
                        is_jingle: false,
                        order: 'shuffle',
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: false,
                        schedule_items: [],
                        is_enabled: true,
                        links: {self: ''},
                    };
            })
            .sort((a, b) => a.weight - b.weight);
    }

    return raw;
};

const fetchAndBuildPlaylists = async (): Promise<void> => {
    const {data} = await axios.get(props.listUrl, {
        params: {rowCount: -1},
    });

    const items: Playlist[] = data.rows as Playlist[] ?? [];

    playlists.value = buildTree(items);
};

const resolveCurrentPlaylistsByCreadcrumbs = (breadcrumbs: PlaylistBreadcrumb[]): Playlist[] => {
    if (breadcrumbs.length === 0) {
        return [...playlists.value];
    }

    let currentPlaylists = playlists.value;
    for (const breadcrumb of breadcrumbs) {
        const breadcrumpPlaylist = currentPlaylists.find((playlist) => playlist.id === breadcrumb.id);

        if (breadcrumpPlaylist?.source !== 'playlists') {
            continue;
        }

        const resolvedPlaylists = breadcrumpPlaylist.playlists.map(
            (member) => playlists.value.find(
                (playlist) => playlist.id === member.id
            )
        );

        if (resolvedPlaylists.some((playlist) => !playlist)) {
            notifyError($gettext('Failed to resolve playlist in group.'));
            return currentPlaylists;
        }

        currentPlaylists = resolvedPlaylists as Playlist[];
    }

    return currentPlaylists;
};

const loadPlaylists = async () => {
    loading.value = true;
    selectedPlaylist.value = undefined;
    playlistBreadcrumbs.value = [];

    try {
        await fetchAndBuildPlaylists();
        currentPlaylists.value = [...playlists.value];
    } catch {
        notifyError($gettext('Failed to load playlists.'));
    } finally {
        loading.value = false;
    }
};

const navigateFromBreadcrumb = (breadcrumbIndex: number = 0): void => {
    playlistBreadcrumbs.value.splice(breadcrumbIndex);
    currentPlaylists.value = resolveCurrentPlaylistsByCreadcrumbs(playlistBreadcrumbs.value);
};

const enterPlaylistGroup = (playlist: Playlist): void => {
    const resolvedPlaylists = playlist.playlists.map(
        (member) => playlists.value.find(
            (fullPlaylist) => fullPlaylist.id === member.id
        )
    );

    if (resolvedPlaylists.some((playlist) => !playlist)) {
        notifyError($gettext('Failed to resolve playlist in group.'));
        return;
    }

    currentPlaylists.value = resolvedPlaylists as Playlist[];

    playlistBreadcrumbs.value.push({
        id: playlist.id,
        name: playlist.name,
    });
};

const isSelected = (playlist: Playlist): boolean =>
    playlist.id === selectedPlaylist.value?.id;

const isSelectable = (playlist: Playlist): boolean =>
    ['songs', 'playlists'].includes(playlist.source) && !isSelected(playlist);

const isAssignable = (playlist: Playlist): boolean => {
    if (selectedPlaylist.value?.source !== 'playlists') {
        return false;
    }

    if (!['songs', 'playlists'].includes(playlist.source)) {
        return false;
    }

    if (playlistBreadcrumbs.value.some((breadcrump) => breadcrump.id === selectedPlaylist.value!.id)) {
        return false;
    }

    return playlist.id !== selectedPlaylist.value.id;
};

const hasButtons = (playlist: Playlist): boolean =>
    isAssignable(playlist) || playlist.source === 'playlists';

const saveMembersForSelected = async (members: PlaylistMember[]): Promise<void> => {
    const group = selectedPlaylist.value;
    if (!group?.links.members) {
        return;
    }

    saving.value = true;
    try {
        await axios.put(group.links.members, {
            members: members.map((member, index) => ({id: member.id, weight: index + 1})),
        });

        const savedId = group.id;
        const savedBreadcrumbs = [...playlistBreadcrumbs.value];

        await fetchAndBuildPlaylists();

        currentPlaylists.value = resolveCurrentPlaylistsByCreadcrumbs(savedBreadcrumbs);
        selectedPlaylist.value = playlists.value.find((playlist) => playlist.id === savedId);

        notifySuccess($gettext('Playlist group updated.'));
    } catch {
        notifyError($gettext('Failed to update playlist group.'));
    } finally {
        saving.value = false;
    }
};

const doAssign = async (playlist: Playlist): Promise<void> => {
    const group = selectedPlaylist.value;
    if (!group || group.source !== 'playlists') {
        return;
    }

    const newMember: PlaylistMember = {
        id: playlist.id,
        name: playlist.name,
        weight: group.playlists.length + 1,
        source: playlist.source,
        num_songs: playlist.num_songs,
        playlists: playlist.playlists,
    };

    await saveMembersForSelected([...playlistMembers.value, newMember]);
};

const doRemove = async (index: number): Promise<void> => {
    const updated = [...playlistMembers.value];
    updated.splice(index, 1);

    await saveMembersForSelected(updated);
};

const doMoveUp = async (index: number): Promise<void> => {
    const updated = [...playlistMembers.value];
    const item = updated.splice(index, 1)[0];
    updated.splice(index - 1, 0, item);

    await saveMembersForSelected(updated);
};

const doMoveDown = async (index: number): Promise<void> => {
    const updated = [...playlistMembers.value];
    const item = updated.splice(index, 1)[0];
    updated.splice(index + 1, 0, item);

    await saveMembersForSelected(updated);
};

const doMoveToTop = async (index: number): Promise<void> => {
    const updated = [...playlistMembers.value];
    const item = updated.splice(index, 1)[0];
    updated.splice(0, 0, item);

    await saveMembersForSelected(updated);
};

const doMoveToBottom = async (index: number): Promise<void> => {
    const updated = [...playlistMembers.value];
    const item = updated.splice(index, 1)[0];
    updated.splice(updated.length, 0, item);

    await saveMembersForSelected(updated);
};
</script>

<style lang="scss">
.breadcrumb-item + .breadcrumb-item::before {
    float: none;
}

.selected-playlist-details {
    background-color: var(--bs-secondary-bg);
}

.list-group-item.active {
    background-color: var(--bs-secondary-bg);
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, .12), rgba(0, 0, 0, .12));
    border-color: var(--bs-list-group-border-color);
    color: var(--bs-heading-color);
}

.playlist-selection-item.selectable {
    cursor: pointer;

    &:hover {
        background-image: linear-gradient(to bottom, rgba(0, 0, 0, .12), rgba(0, 0, 0, .12));
    }
}

.list-group-item:not(.no-drag) {
    cursor: grab;
}

.min-w-0 {
    min-width: 0;
}

.sortable-ghost {
    opacity: 0.4;
}
</style>
