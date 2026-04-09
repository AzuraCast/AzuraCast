<template>
    <section
        v-show="form.type === 'clockwheel'"
        class="card mb-3"
        role="region"
    >
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Clockwheel Steps') }}
            </h2>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                {{ $gettext('Define the sequence of playlists to play from. Each step will play the specified number of songs before advancing to the next step. The sequence repeats from the beginning after the last step.') }}
                {{ $gettext('General Rotation playlists can be added as steps.') }}
            </p>

            <div class="form-check mb-3">
                <input
                    id="clockwheel_suppress_requests"
                    v-model="suppressRequests"
                    type="checkbox"
                    class="form-check-input"
                >
                <label
                    for="clockwheel_suppress_requests"
                    class="form-check-label"
                >
                    {{ $gettext('Handle requests in clockwheel') }}
                </label>
                <small class="form-text text-muted d-block">
                    {{ $gettext('When enabled, the global request queue is suppressed and requests are only played via request slots or steps with "Allow Requests" enabled.') }}
                </small>
            </div>

            <div
                v-if="children.length === 0"
                class="alert alert-info"
            >
                {{ $gettext('No child playlists have been added yet. Add playlists below to define the clockwheel sequence.') }}
            </div>

            <table
                v-if="children.length > 0"
                class="table table-striped mb-3"
            >
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">{{ $gettext('Playlist') }}</th>
                        <th style="width: 20%;">{{ $gettext('Info') }}</th>
                        <th style="width: 10%;">{{ $gettext('Per Step') }}</th>
                        <th style="width: 15%;">{{ $gettext('Allow Requests') }}</th>
                        <th style="width: 20%;">{{ $gettext('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(child, index) in children"
                        :key="index"
                        class="align-middle"
                    >
                        <td>{{ index + 1 }}</td>
                        <td>
                            <template v-if="child.child_playlist_id === null">
                                <span class="badge text-bg-info">
                                    {{ $gettext('Request Slot') }}
                                </span>
                            </template>
                            <template v-else>
                                <select
                                    v-model="child.child_playlist_id"
                                    class="form-select form-select-sm"
                                >
                                    <option
                                        value=""
                                        disabled
                                    >
                                        {{ $gettext('Select a Playlist') }}
                                    </option>
                                    <option
                                        v-for="pl in availablePlaylists"
                                        :key="pl.id"
                                        :value="pl.id"
                                    >
                                        {{ pl.name }}
                                    </option>
                                </select>
                            </template>
                        </td>
                        <td>
                            <template v-if="child.child_playlist_id !== null && getPlaylistMeta(child.child_playlist_id)">
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge text-bg-secondary">
                                        {{ getPlaylistMeta(child.child_playlist_id)!.order }}
                                    </span>
                                    <span
                                        v-if="getPlaylistMeta(child.child_playlist_id)!.is_jingle"
                                        class="badge text-bg-primary"
                                    >
                                        {{ $gettext('Jingle Mode') }}
                                    </span>
                                    <span class="badge text-bg-info">
                                        {{ getPlaylistMeta(child.child_playlist_id)!.num_songs }}
                                    </span>
                                </div>
                            </template>
                            <template v-if="child.child_playlist_id === null">
                                <small class="text-muted">
                                    {{ $gettext('Plays pending listener requests.') }}
                                </small>
                            </template>
                        </td>
                        <td>
                            <input
                                v-model.number="child.song_count"
                                type="number"
                                class="form-control form-control-sm"
                                min="1"
                                max="100"
                            >
                        </td>
                        <td>
                            <template v-if="child.child_playlist_id !== null">
                                <select
                                    v-model="child.request_mode"
                                    class="form-select form-select-sm"
                                >
                                    <option value="none">
                                        {{ $gettext('None') }}
                                    </option>
                                    <option value="any">
                                        {{ $gettext('Any') }}
                                    </option>
                                    <option value="playlist_only">
                                        {{ $gettext('From Playlist') }}
                                    </option>
                                </select>
                            </template>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button
                                    v-if="index > 0"
                                    type="button"
                                    class="btn btn-primary"
                                    :title="$gettext('Move Up')"
                                    @click.prevent="moveUp(index)"
                                >
                                    <icon-bi-chevron-up />
                                </button>
                                <button
                                    v-if="index + 1 < children.length"
                                    type="button"
                                    class="btn btn-primary"
                                    :title="$gettext('Move Down')"
                                    @click.prevent="moveDown(index)"
                                >
                                    <icon-bi-chevron-down />
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    :title="$gettext('Remove')"
                                    @click.prevent="removeChild(index)"
                                >
                                    <icon-bi-x-lg />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                v-if="children.length > 0"
                class="text-muted mb-3"
            >
                {{ $gettext('Total unique songs across all steps:') }}
                <strong>{{ totalUniqueSongs }}</strong>
            </div>

            <div class="d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click.prevent="addChild"
                >
                    {{ $gettext('Add Playlist Step') }}
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-secondary"
                    @click.prevent="addRequestSlot"
                >
                    {{ $gettext('Add Request Slot') }}
                </button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import {ref, onMounted, watch, computed} from "vue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {useApiRouter} from "~/functions/useApiRouter.ts";
import {storeToRefs} from "pinia";
import {useStationsPlaylistsForm} from "~/components/Stations/Playlists/Form/form.ts";
import IconBiChevronUp from "~icons/bi/chevron-up";
import IconBiChevronDown from "~icons/bi/chevron-down";
import IconBiXLg from "~icons/bi/x-lg";

interface ChildItem {
    child_playlist_id: number | '' | null;
    child_playlist_name?: string;
    song_count: number;
    request_mode: 'none' | 'any' | 'playlist_only';
}

interface PlaylistOption {
    id: number;
    name: string;
    type: string;
    order: string;
    is_jingle: boolean;
    num_songs: number;
}

const props = defineProps<{
    editUrl?: string | null;
}>();

const {form} = storeToRefs(useStationsPlaylistsForm());

const {$gettext} = useTranslate();
const {axios} = useAxios();
const {getStationApiUrl} = useApiRouter();

const children = ref<ChildItem[]>([]);
const availablePlaylists = ref<PlaylistOption[]>([]);

const suppressRequests = computed({
    get: () => form.value.backend_options.includes('suppress_requests'),
    set: (val: boolean) => {
        const opts = form.value.backend_options;
        if (val && !opts.includes('suppress_requests')) {
            opts.push('suppress_requests');
        } else if (!val) {
            form.value.backend_options = opts.filter((o: string) => o !== 'suppress_requests');
        }
    }
});

const playlistMetaMap = computed(() => {
    const map = new Map<number, PlaylistOption>();
    for (const pl of availablePlaylists.value) {
        map.set(pl.id, pl);
    }
    return map;
});

const getPlaylistMeta = (id: number | ''): PlaylistOption | undefined => {
    if (id === '') return undefined;
    return playlistMetaMap.value.get(id);
};

const totalUniqueSongs = computed(() => {
    const seenIds = new Set<number>();
    let total = 0;
    for (const child of children.value) {
        if (child.child_playlist_id !== null && child.child_playlist_id !== '' && !seenIds.has(child.child_playlist_id)) {
            seenIds.add(child.child_playlist_id);
            const meta = getPlaylistMeta(child.child_playlist_id);
            if (meta) {
                total += meta.num_songs;
            }
        }
    }
    return total;
});

const currentPlaylistId = computed((): number | null => {
    if (!props.editUrl) return null;
    const parts = props.editUrl.split('/');
    const id = parseInt(parts[parts.length - 1], 10);
    return isNaN(id) ? null : id;
});

const loadPlaylists = async () => {
    try {
        const listUrl = getStationApiUrl('/playlists');
        const {data} = await axios.get(listUrl.value);
        availablePlaylists.value = data
            .filter((pl: any) =>
                pl.type === 'default'
                && pl.id !== currentPlaylistId.value
            )
            .map((pl: any) => ({
                id: pl.id,
                name: pl.name,
                type: pl.type,
                order: pl.order,
                is_jingle: pl.is_jingle,
                num_songs: pl.num_songs
            }));
    } catch {
        // Noop
    }
};

const loadChildren = async (url: string) => {
    try {
        const {data} = await axios.get(url + '/children');
        children.value = data.map((child: any) => ({
            child_playlist_id: child.child_playlist_id ?? null,
            child_playlist_name: child.child_playlist_name,
            song_count: child.song_count,
            request_mode: child.request_mode ?? 'none'
        }));
    } catch {
        children.value = [];
    }
};

const addChild = () => {
    children.value.push({
        child_playlist_id: '',
        song_count: 1,
        request_mode: 'none'
    });
};

const addRequestSlot = () => {
    children.value.push({
        child_playlist_id: null,
        song_count: 1,
        request_mode: 'none'
    });
};

const removeChild = (index: number) => {
    children.value.splice(index, 1);
};

const moveUp = (index: number) => {
    const item = children.value.splice(index, 1)[0];
    children.value.splice(index - 1, 0, item);
};

const moveDown = (index: number) => {
    const item = children.value.splice(index, 1)[0];
    children.value.splice(index + 1, 0, item);
};

onMounted(() => {
    void loadPlaylists();
});

watch(
    () => [props.editUrl, form.value.type],
    ([newUrl, newType]) => {
        if (newUrl && newType === 'clockwheel') {
            void loadChildren(newUrl);
        } else {
            children.value = [];
        }
    },
    {immediate: true}
);

defineExpose({
    children
});
</script>
