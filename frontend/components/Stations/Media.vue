<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_music_files"
    >
        <div class="card-header text-bg-primary">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h2
                        id="hdr_music_files"
                        class="card-title"
                    >
                        {{ $gettext('Music Files') }}
                    </h2>
                </div>
                <div class="col-md-5 text-end">
                    <stations-common-quota
                        ref="$quota"
                        :quota-url="quotaUrl"
                    />
                </div>
            </div>
        </div>

        <info-card v-if="showSftp">
            <p class="mb-0">
                {{ $gettext('You can also upload files in bulk via SFTP.') }}
            </p>

            <template #action>
                <router-link
                    class="btn btn-sm btn-info"
                    :to="{name: 'stations:sftp_users:index'}"
                >
                    {{ $gettext('Manage SFTP Accounts') }}
                </router-link>
            </template>
        </info-card>

        <div class="card-body">
            <breadcrumb
                :current-directory="currentDirectory"
                @change-directory="changeDirectory"
            />

            <file-upload
                :upload-url="uploadUrl"
                :search-phrase="searchPhrase"
                :valid-mime-types="validMimeTypes"
                :current-directory="currentDirectory"
                @relist="onTriggerRelist"
            />

            <media-toolbar
                :batch-url="batchUrl"
                :selected-items="selectedItems"
                :current-directory="currentDirectory"
                :supports-immediate-queue="supportsImmediateQueue"
                :playlists="playlists"
                @add-playlist="onAddPlaylist"
                @relist="onTriggerRelist"
                @create-directory="createDirectory"
                @move-files="moveFiles"
            />
        </div>

        <data-table
            id="station_media"
            ref="$dataTable"
            selectable
            paginated
            select-fields
            :fields="fields"
            :provider="listItemProvider"
            @row-selected="onRowSelected"
            @filtered="onFiltered"
        >
            <template #cell(path)="{ item }">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 pe-2">
                        <template v-if="item.type === FileTypes.Media">
                            <play-button
                                class="btn-lg"
                                :stream="{
                                    title: item.text,
                                    url: item.media!.links.play
                                }"
                            />
                        </template>
                        <template v-else>
                            <span
                                v-if="item.type === FileTypes.Directory"
                                class="file-icon"
                            >
                                <icon-ic-folder/>
                            </span>
                            <span
                                v-else-if="item.type === FileTypes.CoverArt"
                                class="file-icon"
                            >
                                <icon-ic-image/>
                            </span>
                            <span
                                v-else
                                class="file-icon"
                            >
                                <icon-ic-insert-drive-file/>
                            </span>
                        </template>
                    </div>

                    <div class="flex-fill">
                        <template v-if="item.type === FileTypes.Directory">
                            <a
                                class="name"
                                href="#"
                                :title="item.text"
                                @click.prevent="changeDirectory(item.path!)"
                            >
                                {{ item.path_short }}
                            </a>
                        </template>
                        <template v-else-if="item.type === FileTypes.Media">
                            <a
                                class="name"
                                v-if="item.media"
                                :href="item.media.links.play"
                                target="_blank"
                                :title="item.text"
                            >
                                {{ item.text }}
                            </a>
                        </template>
                        <template v-else>
                            <a
                                class="name"
                                :href="item.links.download"
                                target="_blank"
                                :title="item.text"
                            >
                                {{ item.path_short }}
                            </a>
                        </template>
                        <br>
                        <small v-if="item.type === FileTypes.Media">{{ item.path_short }}</small>
                        <small v-else>{{ item.text }}</small>
                    </div>

                    <album-art
                        v-if="item.media?.art"
                        :src="item.media.art"
                        class="flex-shrink-1 ps-2"
                    />
                    <album-art
                        v-else-if="item.type === FileTypes.CoverArt"
                        :src="item.links.download"
                        class="flex-shrink-1 ps-2"
                    />
                </div>
            </template>
            <!-- eslint-disable-next-line -->
            <template #cell(media.length)="{ item }">
                {{ item.media?.length_text }}
            </template>
            <template #cell(size)="{ item }">
                <template v-if="!item.size">
                    &nbsp;
                </template>
                <template v-else>
                    {{ formatFileSize(item.size) }}
                </template>
            </template>
            <template #cell(playlists)="{ item }">
                <MediaPlaylists
                    v-if="item.type === FileTypes.Media"
                    :playlists="item.media?.playlists as ApiStationMediaPlaylist[]"
                    @filter="filter"
                />
                <MediaPlaylists
                    v-else-if="item.type === FileTypes.Directory"
                    :playlists="item.dir?.playlists as ApiStationMediaPlaylist[]"
                    @filter="filter"
                />
                <template v-else>
                    &nbsp;
                </template>
            </template>
            <template #cell(commands)="{ item }">
                <template v-if="item.media?.links?.self">
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="edit(item.media.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                </template>
                <template v-else>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="rename(item.path)"
                    >
                        {{ $gettext('Rename') }}
                    </button>
                </template>
            </template>
        </data-table>
    </section>

    <new-directory-modal
        ref="$newDirectoryModal"
        :current-directory="currentDirectory"
        :mkdir-url="mkdirUrl"
        @relist="onTriggerRelist"
    />

    <move-files-modal
        ref="$moveFilesModal"
        :selected-items="selectedItems"
        :current-directory="currentDirectory"
        :batch-url="batchUrl"
        :list-directories-url="listDirectoriesUrl"
        @relist="onTriggerRelist"
    />

    <rename-modal
        ref="$renameModal"
        :rename-url="renameUrl"
        @relist="onTriggerRelist"
    />

    <edit-modal
        ref="$editModal"
        :custom-fields="customFields"
        :playlists="playlists"
        @relist="onTriggerRelist"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import MediaToolbar from "~/components/Stations/Media/MediaToolbar.vue";
import Breadcrumb from "~/components/Stations/Media/Breadcrumb.vue";
import FileUpload from "~/components/Stations/Media/FileUpload.vue";
import NewDirectoryModal from "~/components/Stations/Media/NewDirectoryModal.vue";
import MoveFilesModal from "~/components/Stations/Media/MoveFilesModal.vue";
import RenameModal from "~/components/Stations/Media/RenameModal.vue";
import EditModal from "~/components/Stations/Media/EditModal.vue";
import StationsCommonQuota from "~/components/Stations/Common/Quota.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed, ref, useTemplateRef, watch} from "vue";
import {forEach, map, partition} from "es-toolkit/compat";
import formatFileSize from "~/functions/formatFileSize";
import InfoCard from "~/components/Common/InfoCard.vue";
import {useRoute, useRouter} from "vue-router";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {
    ApiStationMediaPlaylist,
    ApiStationsVueFilesProps,
    CustomField,
    FileTypes,
    StorageLocationTypes
} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import MediaPlaylists from "~/components/Stations/Media/MediaPlaylists.vue";
import {useStationData} from "~/functions/useStationQuery.ts";
import {FileListRequired, StationsVueFilesPropsRequired} from "~/entities/StationMedia.ts";
import IconIcInsertDriveFile from "~icons/ic/baseline-insert-drive-file";
import IconIcFolder from "~icons/ic/baseline-folder";
import IconIcImage from "~icons/ic/baseline-image";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const props = defineProps<StationsVueFilesPropsRequired>();

export type MediaInitialPlaylist = ApiStationsVueFilesProps['initialPlaylists'][number];

export type MediaRow = FileListRequired;

export type MediaSelectedItems = {
    all: MediaRow[],
    files: string[],
    directories: string[]
}

const stationData = useStationData();
const showSftp = computed(() => stationData.value.features.sftp ?? false);

const {getStationApiUrl} = useApiRouter();
const listUrl = getStationApiUrl('/files/list');
const batchUrl = getStationApiUrl('/files/batch');
const uploadUrl = getStationApiUrl('/files/upload');
const listDirectoriesUrl = getStationApiUrl('/files/directories');
const mkdirUrl = getStationApiUrl('/files/mkdir');
const renameUrl = getStationApiUrl('/files/rename');
const quotaUrl = getStationApiUrl(`/quota/${StorageLocationTypes.StationMedia}`);

const currentDirectory = ref('');

const isFilterString = (str: string) =>
    (str.substring(0, 9) === 'playlist:' || str.substring(0, 8) === 'special:');

const router = useRouter();
const route = useRoute();

watch(
    () => route.params,
    async (newParams) => {
        let path = newParams.path ?? '';
        if (Array.isArray(path)) {
            path = path.join('');
        }

        if (isFilterString(path)) {
            await router.push({
                name: 'stations:files:index',
            });
            filter(path);
        } else {
            currentDirectory.value = path;
        }
    },
    {
        immediate: true
    }
);

const {$gettext} = useTranslate();

const {formatTimestampAsDateTime} = useStationDateTimeFormatter();

const listItemProvider = useApiItemProvider<MediaRow>(
    listUrl,
    queryKeyWithStation(
        [QueryKeys.StationMedia, 'files', currentDirectory]
    ),
    {
        staleTime: 2 * 60 * 1000
    },
    (config) => {
        config.params.currentDirectory = currentDirectory.value;
        return config;
    }
);

const fields = computed<DataTableField<MediaRow>[]>(() => {
    const fields: DataTableField<MediaRow>[] = [
        {key: 'path', isRowHeader: true, label: $gettext('Name'), sortable: true},
        {key: 'media.title', label: $gettext('Title'), sortable: true, selectable: true, visible: false},
        {
            key: 'media.artist',
            label: $gettext('Artist'),
            sortable: true,
            selectable: true,
            visible: false
        },
        {key: 'media.album', label: $gettext('Album'), sortable: true, selectable: true, visible: false},
        {key: 'media.genre', label: $gettext('Genre'), sortable: true, selectable: true, visible: false},
        {key: 'media.isrc', label: $gettext('ISRC'), sortable: true, selectable: true, visible: false},
        {key: 'media.length', label: $gettext('Length'), sortable: true, selectable: true, visible: true}
    ];

    forEach({...props.customFields}, (field: CustomField) => {
        fields.push({
            key: 'media.custom_fields[' + field.short_name + ']',
            label: field.name,
            sortable: true,
            selectable: true,
            visible: false
        });
    });

    fields.push(
        {key: 'size', label: $gettext('Size'), sortable: true, selectable: true, visible: true},
        {
            key: 'timestamp',
            label: $gettext('Modified'),
            sortable: true,
            formatter: (value) => formatTimestampAsDateTime(value),
            selectable: true,
            visible: true
        },
        {
            key: 'media.uploaded_at',
            label: $gettext('Uploaded Time'),
            sortable: true,
            formatter: (value) => formatTimestampAsDateTime(value),
            selectable: true,
            visible: false
        },
        {
            key: 'media.mtime',
            label: $gettext('Last Processed Time'),
            sortable: true,
            formatter: (value) => formatTimestampAsDateTime(value),
            selectable: true,
            visible: false
        },
        {
            key: 'playlists',
            label: $gettext('Playlists'),
            sortable: false,
            selectable: true,
            visible: true
        },
        {key: 'commands', label: $gettext('Actions'), sortable: false, class: 'shrink'}
    );

    return fields;
});

const playlists = ref<MediaInitialPlaylist[]>(props.initialPlaylists.slice());
const selectedItems = ref<MediaSelectedItems>({
    all: [],
    files: [],
    directories: []
});

const searchPhrase = ref('');

const onRowSelected = (items: MediaRow[]) => {
    const splitItems = partition(items, (row) => row.type === FileTypes.Directory);

    selectedItems.value = {
        all: items,
        files: map(splitItems[1], 'path'),
        directories: map(splitItems[0], 'path')
    };
};

const $dataTable = useTemplateRef('$dataTable');

const onTriggerNavigate = () => {
    $dataTable.value?.navigate();
};

const filter = (newFilter: string) => {
    $dataTable.value?.setFilter(newFilter);
};

const $quota = useTemplateRef('$quota');

const onTriggerRelist = () => {
    void listItemProvider.refresh(false);

    $quota.value?.update();
};

const onAddPlaylist = (row: MediaInitialPlaylist) => {
    playlists.value.push(row);
};

const onFiltered = (newFilter: string) => {
    searchPhrase.value = newFilter;
};

const $renameModal = useTemplateRef('$renameModal');

const rename = (path: string) => {
    $renameModal.value?.open(path);
};

const $editModal = useTemplateRef('$editModal');

const edit = (recordUrl: string) => {
    $editModal.value?.open(recordUrl);
};

const $newDirectoryModal = useTemplateRef('$newDirectoryModal');

const createDirectory = () => {
    $newDirectoryModal.value?.open();
}

const $moveFilesModal = useTemplateRef('$moveFilesModal');

const moveFiles = () => {
    $moveFilesModal.value?.open();
}

const changeDirectory = (newDir: string) => {
    void router.push({
        name: 'stations:files:index',
        params: {
            path: newDir
        }
    });

    onTriggerNavigate();
};
</script>
