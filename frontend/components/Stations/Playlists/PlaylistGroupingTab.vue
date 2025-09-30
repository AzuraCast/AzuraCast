<template>
    <tab
        id="playlist_grouping"
        :label="$gettext('Playlist Grouping')"
    >
        <div class="card-body-flush">
            <div class="row gx-1 pt-1 overflow-hidden">
                <div class="col-6">
                    <h4 class="bg-primary text-bg-primary text-center p-3 mb-0 shadow">
                        Playlists
                    </h4>

                    <nav
                        v-if="playlistBreadcrumps.length"
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
                                    <icon :icon="IconHome" />
                                </a>
                            </li>

                            <template v-for="(breadcrumb, index) in playlistBreadcrumps">
                                <template v-if="index < (playlistBreadcrumps.length - 1)">
                                    <li class="breadcrumb-item text-nowrap">
                                        <a
                                            href="#"
                                            @click.prevent="navigateFromBreadcrumb(index + 1)"
                                            class="text-nowrap"
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

                    <ul class="list-group list-group-flush h-100 shadow">
                        <li v-if="currentPlaylists.length === 0">
                            <div class="p-5 text-center fs-5">
                                No playlists available
                            </div>
                        </li>
                        <li
                            v-for="item in currentPlaylists"
                            :key="item.id"
                            class="list-group-item p-0"
                            :class="{active: isSelected(item)}"
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
                                        <div class="d-flex flex-grow-1 justify-content-between align-items-start">
                                            <span class="pr-2 fs-5">{{ item.name }}</span>

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

                                        <div
                                            class="text-truncate text-muted mb-3"
                                            :title="item.description"
                                        >
                                            {{ item.description }}
                                        </div>

                                        <div>
                                            <span class="badge text-bg-secondary">
                                                <div class="d-flex align-items-center">
                                                    <template v-if="item.source === 'songs'">
                                                        <icon :icon="IconLibraryMusic"/>
                                                        <div class="ps-2">{{ $gettext('Song-based') }}</div>
                                                    </template>
                                                    <template v-else-if="item.source === 'playlists'">
                                                        <icon :icon="IconPlaylist"/>
                                                        <div class="ps-2">{{ $gettext('Playlist Group') }}</div>
                                                    </template>
                                                    <template v-else>
                                                        <icon :icon="IconPublic"/>
                                                        <div class="ps-2">{{ $gettext('Remote URL') }}</div>
                                                    </template>
                                                </div>
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="hasButtons(item)"
                                        class="btn-group-vertical ms-3"
                                    >
                                        <button
                                            v-if="isAssignable(item)"
                                            type="button"
                                            title="Assign to current playlist"
                                            class="btn btn-primary"
                                        >
                                            <icon :icon="IconDriveFileMove"/>
                                        </button>

                                        <button
                                            v-if="item.source === 'playlists'"
                                            type="button"
                                            title="Enter playlist group"
                                            class="btn btn-secondary"
                                            @click="enterPlaylistGroup(item)"
                                        >
                                            <icon :icon="IconFolderOpen"/>
                                        </button>
                                    </div>
                                </div>
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="col-6">
                    <h4 class="bg-primary text-bg-primary text-center p-3 mb-0 shadow">
                        Playlist Contents
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
                                v-if="selectedPlaylist.source === 'songs' && selectedPlaylist.order === 'sequential'"
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

                    <ul class="list-group list-group-flush h-100 shadow">
                        <li v-if="selectedPlaylist === undefined">
                            <div class="p-5 text-center fs-5">
                                No playlist selected
                            </div>
                        </li>

                        <li v-else-if="selectedPlaylist.source === 'songs' && selectedPlaylist.num_songs === 0">
                            <div class="p-5 text-center fs-5">
                                No songs available
                            </div>
                        </li>

                        <li v-else-if="selectedPlaylist.source === 'playlists' && selectedPlaylist.playlists.length === 0">
                            <div class="p-5 text-center fs-5">
                                No playlists available
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'songs'"
                            v-for="mediaItem in selectedPlaylistMediaItems"
                            class="list-group-item"
                        >
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <album-art
                                        :src="mediaItem.art"
                                        class="flex-shrink-1 pe-3"
                                    />

                                    <div class="d-flex flex-column flex-grow-1">
                                        <span class="fs-5">{{ mediaItem.title }}</span>
                                        <span class="fs-6">{{ mediaItem.artist }}</span>
                                    </div>
                                </div>

                                <span
                                    class="badge bg-primary rounded-pill"
                                    v-text="mediaItem.length_text"
                                />
                            </div>
                        </li>

                        <li
                            v-else-if="selectedPlaylist.source === 'playlists'"
                            v-for="playlist in selectedPlaylist.playlists"
                            class="list-group-item"
                        >
                            <div class="d-flex flex-column">
                                <div class="d-flex flex-grow-1 justify-content-between align-items-start mb-2">
                                    <span class="flex-grow-1 pr-2 fs-5">{{ playlist.name }}</span>

                                    <span
                                        v-if="playlist.source === 'songs'"
                                        class="badge bg-primary rounded-pill"
                                        v-text="playlist.num_songs"
                                    />
                                    <span
                                        v-else-if="playlist.source === 'playlists'"
                                        class="badge bg-primary rounded-pill"
                                        v-text="playlist.playlists.length"
                                    />
                                </div>

                                <div>
                                    <span class="badge text-bg-secondary">
                                        <div class="d-flex align-items-center">
                                            <template v-if="playlist.source === 'songs'">
                                                <icon :icon="IconLibraryMusic"/>
                                                <div class="ps-2">{{ $gettext('Song-based') }}</div>
                                            </template>
                                            <template v-else-if="playlist.source === 'playlists'">
                                                <icon :icon="IconPlaylist"/>
                                                <div class="ps-2">{{ $gettext('Playlist Group') }}</div>
                                            </template>
                                            <template v-else>
                                                <icon :icon="IconPublic"/>
                                                <div class="ps-2">{{ $gettext('Remote URL') }}</div>
                                            </template>
                                        </div>
                                    </span>
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
import Icon from "~/components/Common/Icons/Icon.vue";
import {
    IconHome,
    IconLibraryMusic,
    IconPlaylist,
    IconPublic,
    IconDriveFileMove,
    IconFolderOpen
} from "~/components/Common/Icons/icons.ts";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {ref, watch} from "vue";
import {useLuxon} from "~/vendor/luxon";
import {useDraggable} from "vue-draggable-plus"; // @TODO: add draggable feature

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
    schedule_items: string[],
    is_enabled: boolean,
    num_songs: number,
    playlists: Playlist[]
};

export type MediaItem = {
    artist: string,
    title: string,
    length_text: string,
    art: string
};

export type PlaylistBreadcrump = {
    id: number,
    name: string
};

const selectedPlaylist = ref<Playlist>();
const selectedPlaylistMediaItems = ref<MediaItem[]>([]);

watch(selectedPlaylist, (item: Playlist|undefined) => {
    if (item === undefined) {
        selectedPlaylistMediaItems.value = [];
    } else if (item.source === 'songs') {
        // @TODO: Load media items for selected playlist instead of mocking
        selectedPlaylistMediaItems.value = [];
        for (let index = 0; index < item.num_songs; index++) {
            selectedPlaylistMediaItems.value.push({
                artist: randomWord(9),
                title: randomWord(16),
                length_text: formatLength(randomInt(60, 500)),
                art: '/api/station/1/art/49d74f4dadd63d1b3415d7b3'
            });
        }
    } else {
        selectedPlaylistMediaItems.value = [];
    }
});

const randomWord = (length = 5) => {
  const consonants = 'bcdfghjklmnpqrstvwxyz';
  const vowels = 'aeiou';
  let word = '';
  for (let i = 0; i < length; i++) {
    word += (i % 2 === 0)
      ? consonants[Math.floor(Math.random() * consonants.length)]
      : vowels[Math.floor(Math.random() * vowels.length)];
  }
  return word;
}

const randomInt = (min: number, max: number) => {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

const {Duration} = useLuxon();
const formatLength = (length: number) => {
    const duration = Duration.fromMillis(length * 1000);
    return duration.rescale().toHuman({unitDisplay: 'short'});
};

const playlists = ref<Playlist[]>([
    {
        id: 1,
        name: 'Morning',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr. At vero eos et accusam et justo duo dolores et ea rebum.',
        source: 'playlists',
        type: 'default',
        order: 'shuffle',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: false,
        schedule_items: ['test'],
        is_enabled: true,
        is_jingle: false,
        num_songs: 0,
        playlists: [
            {
                id: 8,
                name: 'test 1',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'shuffle',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 75,
                playlists: []
            },
            {
                id: 9,
                name: 'test 2',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 8,
                playlists: []
            },
        ]
    },
    {
        id: 2,
        name: 'Evening',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'playlists',
        type: 'default',
        order: 'sequential',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: false,
        schedule_items: ['test'],
        is_enabled: true,
        is_jingle: false,
        num_songs: 0,
        playlists: [
            {
                id: 15,
                name: 'test 3',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'shuffle',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: true,
                num_songs: 69,
                playlists: []
            },
            {
                id: 16,
                name: 'test 4',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'playlists',
                type: 'default',
                order: 'sequential',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: false,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 0,
                playlists: [
                    {
                        id: 17,
                        name: 'test 5',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'songs',
                        type: 'default',
                        order: 'shuffle',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: true,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 8,
                        playlists: []
                    },
                    {
                        id: 22,
                        name: 'test 6',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'songs',
                        type: 'default',
                        order: 'random',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: true,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 16,
                        playlists: []
                    },
                ]
            },
            {
                id: 18,
                name: 'test 7',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'playlists',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: false,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 0,
                playlists: [
                    {
                        id: 19,
                        name: 'test 8',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'songs',
                        type: 'default',
                        order: 'random',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: true,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 8,
                        playlists: []
                    },
                    {
                        id: 20,
                        name: 'test 9',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'songs',
                        type: 'default',
                        order: 'random',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: true,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 16,
                        playlists: []
                    },
                    {
                        id: 21,
                        name: 'test 10',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'songs',
                        type: 'default',
                        order: 'random',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: true,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 16,
                        playlists: []
                    },
                ]
            },
            {
                id: 22,
                name: 'test 11',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: false,
                is_jingle: false,
                num_songs: 12,
                playlists: []
            },
        ]
    },
    {
        id: 3,
        name: 'Late Night',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'playlists',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: false,
        schedule_items: [],
        is_enabled: true,
        is_jingle: false,
        num_songs: 0,
        playlists: [
            {
                id: 10,
                name: 'test 3',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 75,
                playlists: []
            },
            {
                id: 11,
                name: 'test 4',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 134,
                playlists: []
            },
            {
                id: 12,
                name: 'test 5',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 4,
                playlists: []
            },
            {
                id: 13,
                name: 'test 6',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 6,
                playlists: []
            },
            {
                id: 14,
                name: 'test 7',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'songs',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: true,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 12,
                playlists: []
            },
        ]
    },
    {
        id: 4,
        name: 'Jazz',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'songs',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: true,
        schedule_items: [],
        is_enabled: true,
        is_jingle: false,
        num_songs: 12,
        playlists: []
    },
    {
        id: 5,
        name: 'Disco',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'songs',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: true,
        schedule_items: [],
        is_enabled: true,
        is_jingle: false,
        num_songs: 123,
        playlists: []
    },
    {
        id: 6,
        name: 'Metal',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'songs',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: true,
        schedule_items: ['test'],
        is_enabled: false,
        is_jingle: true,
        num_songs: 1234,
        playlists: []
    },
    {
        id: 23,
        name: 'Deep Nested Playlist 1',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'playlists',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: false,
        schedule_items: [],
        is_enabled: true,
        is_jingle: false,
        num_songs: 0,
        playlists: [
            {
                id: 24,
                name: 'Deep Nested Playlist 2',
                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                source: 'playlists',
                type: 'default',
                order: 'random',
                weight: 1,
                play_per_songs: 0,
                play_per_minutes: 0,
                play_per_hour_minute: 0,
                include_in_on_demand: false,
                schedule_items: [],
                is_enabled: true,
                is_jingle: false,
                num_songs: 0,
                playlists: [
                    {
                        id: 25,
                        name: 'Deep Nested Playlist 3',
                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                        source: 'playlists',
                        type: 'default',
                        order: 'random',
                        weight: 1,
                        play_per_songs: 0,
                        play_per_minutes: 0,
                        play_per_hour_minute: 0,
                        include_in_on_demand: false,
                        schedule_items: [],
                        is_enabled: true,
                        is_jingle: false,
                        num_songs: 0,
                        playlists: [
                            {
                                id: 26,
                                name: 'Deep Nested Playlist 4',
                                description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                                source: 'playlists',
                                type: 'default',
                                order: 'random',
                                weight: 1,
                                play_per_songs: 0,
                                play_per_minutes: 0,
                                play_per_hour_minute: 0,
                                include_in_on_demand: false,
                                schedule_items: [],
                                is_enabled: true,
                                is_jingle: false,
                                num_songs: 0,
                                playlists: [
                                    {
                                        id: 27,
                                        name: 'Deep Nested Playlist 5',
                                        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                                        source: 'playlists',
                                        type: 'default',
                                        order: 'random',
                                        weight: 1,
                                        play_per_songs: 0,
                                        play_per_minutes: 0,
                                        play_per_hour_minute: 0,
                                        include_in_on_demand: false,
                                        schedule_items: [],
                                        is_enabled: true,
                                        is_jingle: false,
                                        num_songs: 0,
                                        playlists: []
                                    },
                                ]
                            },
                        ]
                    },
                ]
            },
        ]
    },
    {
        id: 7,
        name: 'External Radio',
        description: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
        source: 'remote_url',
        type: 'default',
        order: 'random',
        weight: 1,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_on_demand: false,
        schedule_items: ['string'],
        is_enabled: true,
        is_jingle: false,
        num_songs: 0,
        playlists: []
    }
]);

const currentPlaylists = ref<Playlist[]>([...playlists.value]);

const playlistBreadcrumps = ref<PlaylistBreadcrump[]>([]);

const navigateFromBreadcrumb = (breadcrumpIndex: number = 0): void => {
    playlistBreadcrumps.value.splice(breadcrumpIndex);

    if (playlistBreadcrumps.value.length === 0) {
        currentPlaylists.value = playlists.value;
        return;
    }

    let currentPlaylistsForBreadcrumb = playlists.value;
    playlistBreadcrumps.value.forEach((breadcrumb: PlaylistBreadcrump) => {
        currentPlaylistsForBreadcrumb.forEach((playlist: Playlist) => {
            if (playlist.id === breadcrumb.id) {
                currentPlaylistsForBreadcrumb = playlist.playlists
            }
        });
    });

    currentPlaylists.value = currentPlaylistsForBreadcrumb;
}

const enterPlaylistGroup = (playlist: Playlist): void => {
    currentPlaylists.value = playlist.playlists;

    playlistBreadcrumps.value.push(<PlaylistBreadcrump>{
        id: playlist.id,
        name: playlist.name
    });
};

const isSelected = (playlist: Playlist): boolean => {
    return playlist.id === selectedPlaylist.value?.id;
}

const isSelectable = (playlist: Playlist): boolean => {
    return ['songs', 'playlists'].includes(playlist.source) && !isSelected(playlist);
}

const isAssignable = (playlist: Playlist): boolean => {
    if (selectedPlaylist.value === undefined) {
        return false;
    }

    if (!['songs', 'playlists'].includes(playlist.source)) {
        return false;
    }

    if (playlist.id === selectedPlaylist.value.id) {
        return false;
    }

    return true;
};

const hasButtons = (playlist: Playlist): boolean => {
    return isAssignable(playlist) || playlist.source === 'playlists';
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

.min-w-0 {
    min-width: 0;
}
</style>
