<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_playlists"
    >
        <div class="card-header text-bg-primary">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2
                        id="hdr_playlists"
                        class="card-title"
                    >
                        {{ $gettext('Playlists') }}
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <time-zone />
                </div>
            </div>
        </div>

        <div class="card-body">
            <tabs
                content-class="mt-3"
                destroy-on-hide
            >
                <tab
                    id="all_playlists"
                    :label="$gettext('All Playlists')"
                >
                    <div class="card-body-flush">
                        <div class="card-body buttons">
                            <add-button
                                :text="$gettext('Add Playlist')"
                                @click="doCreate"
                            />
                        </div>
                        
                        <data-table
                            id="station_playlists"
                            paginated
                            :fields="fields"
                            :provider="listItemProvider"
                            detailed
                        >
                            <template #cell(name)="row">
                                <h5 class="m-0">
                                    {{ row.item.name }}
                                </h5>
                                <p v-if="row.item.description" class="text-muted mb-1">
                                    {{ row.item.description }}
                                </p>
                                <div class="badges">
                                    <span class="badge text-bg-secondary">
                                        <template v-if="row.item.source === 'songs'">
                                            {{ $gettext('Song-based') }}
                                        </template>
                                        <template v-else>
                                            {{ $gettext('Remote URL') }}
                                        </template>
                                    </span>
                                    <span
                                        v-if="row.item.is_jingle"
                                        class="badge text-bg-primary"
                                    >
                                        {{ $gettext('Jingle Mode') }}
                                    </span>
                                    <span
                                        v-if="row.item.source === 'songs' && row.item.order === 'sequential'"
                                        class="badge text-bg-info"
                                    >
                                        {{ $gettext('Sequential') }}
                                    </span>
                                    <span
                                        v-if="row.item.include_in_on_demand"
                                        class="badge text-bg-info"
                                    >
                                        {{ $gettext('On-Demand') }}
                                    </span>
                                    <span
                                        v-if="row.item.schedule_items.length > 0"
                                        class="badge text-bg-info"
                                    >
                                        {{ $gettext('Scheduled') }}
                                    </span>
                                    <span
                                        v-if="!row.item.is_enabled"
                                        class="badge text-bg-danger"
                                    >
                                        {{ $gettext('Disabled') }}
                                    </span>
                                </div>
                            </template>
                            <template #cell(scheduling)="{ item }">
                                <template v-if="!item.is_enabled">
                                    {{ $gettext('Disabled') }}
                                </template>
                                <template v-else-if="item.source !== 'songs'">
                                    {{ $gettext('Remote URL') }}
                                </template>
                                <template v-else-if="item.type === 'default'">
                                    {{ $gettext('General Rotation') }}<br>
                                    {{ $gettext('Weight') }}: {{ item.weight }}
                                </template>
                                <template v-else-if="item.type === 'once_per_x_songs'">
                                    {{
                                        $gettext(
                                            'Once per %{songs} Songs',
                                            {songs: item.play_per_songs}
                                        )
                                    }}
                                </template>
                                <template v-else-if="item.type === 'once_per_x_minutes'">
                                    {{
                                        $gettext(
                                            'Once per %{minutes} Minutes',
                                            {minutes: item.play_per_minutes}
                                        )
                                    }}
                                </template>
                                <template v-else-if="item.type === 'once_per_hour'">
                                    {{
                                        $gettext(
                                            'Once per Hour (at %{minute})',
                                            {minute: item.play_per_hour_minute}
                                        )
                                    }}
                                </template>
                                <template v-else>
                                    {{ $gettext('Custom') }}
                                </template>
                            </template>
                            <template #cell(num_songs)="row">
                                <template v-if="row.item.source === 'songs'">
                                    <router-link
                                        :to="{
                                            name: 'stations:files:index',
                                            params: {
                                                path: 'playlist:'+row.item.short_name
                                            }
                                        }"
                                    >
                                        {{ row.item.num_songs }}
                                    </router-link>

                                    ({{ formatLength(row.item.total_length) }})
                                </template>
                                <template v-else>
                                    &nbsp;
                                </template>
                            </template>
                            <template #cell(actions)="{ item, isActive, toggleDetails }">
                                <div class="btn-group btn-group-sm">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="doEdit(item.links.self)"
                                    >
                                        {{ $gettext('Edit') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        @click="doDelete(item.links.self)"
                                    >
                                        {{ $gettext('Delete') }}
                                    </button>

                                    <button
                                        class="btn btn-sm btn-secondary"
                                        type="button"
                                        @click="toggleDetails()"
                                    >
                                        <icon-bi-contract v-if="isActive"/>
                                        <icon-bi-expand v-else/>

                                        {{ $gettext('More') }}
                                    </button>
                                </div>
                            </template>
                            <template #detail="{ item }">
                                <div
                                    class="buttons"
                                    style="line-height: 2.5;"
                                >
                                    <button
                                        v-if="item.links.order"
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        @click="doReorder(item.links.order)"
                                    >
                                        {{ $gettext('Reorder') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm"
                                        :class="(item.is_enabled) ? 'btn-warning' : 'btn-success'"
                                        @click="doModify(item.links.toggle)"
                                    >
                                        {{ (item.is_enabled) ? $gettext('Disable') : $gettext('Enable') }}
                                    </button>
                                    <button
                                        v-if="item.links.empty"
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        @click="doEmpty(item.links.empty)"
                                    >
                                        {{ $gettext('Empty') }}
                                    </button>
                                    <button
                                        v-if="item.links.reshuffle"
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doModify(item.links.reshuffle)"
                                    >
                                        {{ $gettext('Reshuffle') }}
                                    </button>
                                    <button
                                        v-if="item.links.import"
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doImport(item.links.import)"
                                    >
                                        {{ $gettext('Import from PLS/M3U') }}
                                    </button>
                                    <button
                                        v-if="item.links.queue"
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doQueue(item.links.queue)"
                                    >
                                        {{ $gettext('Playback Queue') }}
                                    </button>
                                    <button
                                        v-if="item.links.applyto"
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doApplyTo(item.links.applyto)"
                                    >
                                        {{ $gettext('Apply to Folders') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doClone(item.name, item.links.clone)"
                                    >
                                        {{ $gettext('Duplicate') }}
                                    </button>
                                    <a
                                        v-for="format in ['pls', 'm3u']"
                                        :key="format"
                                        class="btn btn-sm btn-secondary"
                                        :href="item.links.export[format]"
                                        target="_blank"
                                    >
                                        {{
                                            $gettext(
                                                'Export %{format}',
                                                {format: format.toUpperCase()}
                                            )
                                        }}
                                    </a>
                                </div>
                            </template>
                        </data-table>
                    </div>
                </tab>
                <schedule-view-tab
                    ref="$scheduleTab"
                    :schedule-url="scheduleUrl"
                    @click="doCalendarClick"
                />
            </tabs>
        </div>
    </section>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="() => relist()"
        @needs-restart="() => mayNeedRestart()"
    />
    <reorder-modal ref="$reorderModal" />
    <queue-modal ref="$queueModal" />
    <reorder-modal ref="$reorderModal" />
    <import-modal
        ref="$importModal"
        @relist="() => relist()"
    />
    <clone-modal
        ref="$cloneModal"
        @relist="() => relist()"
        @needs-restart="() => mayNeedRestart()"
    />
    <apply-to-modal
        ref="$applyToModal"
        @relist="() => relist()"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Stations/Playlists/EditModal.vue";
import ReorderModal from "~/components/Stations/Playlists/ReorderModal.vue";
import ImportModal from "~/components/Stations/Playlists/ImportModal.vue";
import QueueModal from "~/components/Stations/Playlists/QueueModal.vue";
import CloneModal from "~/components/Stations/Playlists/CloneModal.vue";
import ApplyToModal from "~/components/Stations/Playlists/ApplyToModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {useLuxon} from "~/vendor/luxon";
import TimeZone from "~/components/Stations/Common/TimeZone.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import AddButton from "~/components/Common/AddButton.vue";
import ScheduleViewTab from "~/components/Stations/Common/ScheduleViewTab.vue";
import {EventImpl} from "@fullcalendar/core/internal";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";
import IconBiContract from "~icons/bi/chevron-contract";
import IconBiExpand from "~icons/bi/chevron-expand";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const listUrl = getStationApiUrl('/playlists');
const scheduleUrl = getStationApiUrl('/playlists/schedule');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Playlist'), sortable: true},
    {key: 'scheduling', label: $gettext('Scheduling'), sortable: false},
    {key: 'num_songs', label: $gettext('# Songs'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation([QueryKeys.StationPlaylists])
);

const {Duration} = useLuxon();

const formatLength = (length: number) => {
    if (0 === length) {
        return $gettext('None');
    }

    const duration = Duration.fromMillis(length * 1000);
    return duration.rescale().toHuman();
};

const $scheduleTab = useTemplateRef('$scheduleTab');

const relist = () => {
    void listItemProvider.refresh();
    $scheduleTab.value?.refresh();
}

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event: EventImpl) => {
    doEdit(event.extendedProps.edit_url);
};

const $reorderModal = useTemplateRef('$reorderModal');

const doReorder = (url: string) => {
    $reorderModal.value?.open(url);
};

const $queueModal = useTemplateRef('$queueModal');

const doQueue = (url: string) => {
    $queueModal.value?.open(url);
};

const $importModal = useTemplateRef('$importModal');

const doImport = (url: string) => {
    $importModal.value?.open(url);
};

const $cloneModal = useTemplateRef('$cloneModal');

const doClone = (name: string, url: string) => {
    $cloneModal.value?.open(name, url);
};

const $applyToModal = useTemplateRef('$applyToModal');

const doApplyTo = (url: string) => {
    $applyToModal.value?.open(url);
}

const {mayNeedRestart: originalMayNeedRestart} = useMayNeedRestart();

const stationData = useStationData();
const {useManualAutoDj} = toRefs(stationData);

const mayNeedRestart = () => {
    if (!useManualAutoDj.value) {
        return;
    }

    originalMayNeedRestart();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doModify = async (url: string) => {
    const {data} = await axios.put(url);

    mayNeedRestart();

    notifySuccess(data.message);
    relist();
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Playlist?'),
    () => {
        relist();
        mayNeedRestart();
    },
);

const {doDelete: doEmpty} = useConfirmAndDelete(
    $gettext('Clear all media from playlist?'),
    () => {
        relist();
        mayNeedRestart();
    },
);
</script>
