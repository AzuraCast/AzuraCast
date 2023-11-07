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
                            ref="$datatable"
                            paginated
                            :fields="fields"
                            :api-url="listUrl"
                            detailed
                        >
                            <template #cell(actions)="{ item, toggleDetails }">
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
                                        {{ $gettext('More') }}
                                    </button>
                                </div>
                            </template>
                            <template #cell(name)="row">
                                <h5 class="m-0">
                                    {{ row.item.name }}
                                </h5>
                                <div>
                                    <span class="badge text-bg-secondary me-1">
                                        <template v-if="row.item.source === 'songs'">
                                            {{ $gettext('Song-based') }}
                                        </template>
                                        <template v-else>
                                            {{ $gettext('Remote URL') }}
                                        </template>
                                    </span>
                                    <span
                                        v-if="row.item.is_jingle"
                                        class="badge text-bg-primary me-1"
                                    >
                                        {{ $gettext('Jingle Mode') }}
                                    </span>
                                    <span
                                        v-if="row.item.source === 'songs' && row.item.order === 'sequential'"
                                        class="badge text-bg-info me-1"
                                    >
                                        {{ $gettext('Sequential') }}
                                    </span>
                                    <span
                                        v-if="row.item.include_in_on_demand"
                                        class="badge text-bg-info me-1"
                                    >
                                        {{ $gettext('On-Demand') }}
                                    </span>
                                    <span
                                        v-if="row.item.include_in_automation"
                                        class="badge text-bg-success me-1"
                                    >
                                        {{ $gettext('Auto-Assigned') }}
                                    </span>
                                    <span
                                        v-if="!row.item.is_enabled"
                                        class="badge text-bg-danger me-1"
                                    >
                                        {{ $gettext('Disabled') }}
                                    </span>
                                </div>
                            </template>
                            <template #cell(scheduling)="{ item }">
                                <template v-if="!item.is_enabled">
                                    {{ $gettext('Disabled') }}
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
                            <template #detail="{ item }">
                                <div
                                    class="buttons"
                                    style="line-height: 2.5;"
                                >
                                    <button
                                        type="button"
                                        class="btn btn-sm"
                                        :class="toggleButtonClass(item)"
                                        @click="doModify(item.links.toggle)"
                                    >
                                        {{ langToggleButton(item) }}
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
                                        v-if="item.links.order"
                                        type="button"
                                        class="btn btn-sm btn-secondary"
                                        @click="doReorder(item.links.order)"
                                    >
                                        {{ $gettext('Reorder') }}
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
                <tab
                    id="schedule_view"
                    :label="$gettext('Schedule View')"
                >
                    <div class="card-body-flush">
                        <schedule
                            ref="$schedule"
                            :timezone="timezone"
                            :schedule-url="scheduleUrl"
                            @click="doCalendarClick"
                        />
                    </div>
                </tab>
            </tabs>
        </div>
    </section>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
    <reorder-modal ref="$reorderModal" />
    <queue-modal ref="$queueModal" />
    <reorder-modal ref="$reorderModal" />
    <import-modal
        ref="$importModal"
        @relist="relist"
    />
    <clone-modal
        ref="$cloneModal"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
    <apply-to-modal
        ref="$applyToModal"
        @relist="relist"
    />
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import Schedule from '~/components/Common/ScheduleView.vue';
import EditModal from './Playlists/EditModal.vue';
import ReorderModal from './Playlists/ReorderModal.vue';
import ImportModal from './Playlists/ImportModal.vue';
import QueueModal from './Playlists/QueueModal.vue';
import CloneModal from './Playlists/CloneModal.vue';
import ApplyToModal from "./Playlists/ApplyToModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {useLuxon} from "~/vendor/luxon";
import {getStationApiUrl} from "~/router";
import TimeZone from "~/components/Stations/Common/TimeZone.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import AddButton from "~/components/Common/AddButton.vue";

const props = defineProps({
    useManualAutoDj: {
        type: Boolean,
        required: true
    },
});

const listUrl = getStationApiUrl('/playlists');
const scheduleUrl = getStationApiUrl('/playlists/schedule');

const {timezone} = useAzuraCastStation();

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Playlist'), sortable: true},
    {key: 'scheduling', label: $gettext('Scheduling'), sortable: false},
    {key: 'num_songs', label: $gettext('# Songs'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const toggleButtonClass = (record) => {
    return (record.is_enabled)
        ? 'btn-warning'
        : 'btn-success';
}

const langToggleButton = (record) => {
    return (record.is_enabled)
        ? $gettext('Disable')
        : $gettext('Enable');
};

const {Duration} = useLuxon();

const formatLength = (length) => {
    if (0 === length) {
        return $gettext('None');
    }

    const duration = Duration.fromMillis(length * 1000);
    return duration.rescale().toHuman();
};

const $datatable = ref<InstanceType<typeof DataTable> | null>(null);
const $schedule = ref<InstanceType<typeof Schedule> | null>(null);

const relist = () => {
    $datatable.value?.refresh();
};

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event) => {
    doEdit(event.extendedProps.edit_url);
};

const $reorderModal = ref<InstanceType<typeof ReorderModal> | null>(null);

const doReorder = (url) => {
    $reorderModal.value?.open(url);
};

const $queueModal = ref<InstanceType<typeof QueueModal> | null>(null);

const doQueue = (url) => {
    $queueModal.value?.open(url);
};

const $importModal = ref<InstanceType<typeof ImportModal> | null>(null);

const doImport = (url) => {
    $importModal.value?.open(url);
};

const $cloneModal = ref<InstanceType<typeof CloneModal> | null>(null);

const doClone = (name, url) => {
    $cloneModal.value?.open(name, url);
};

const $applyToModal = ref<InstanceType<typeof ApplyToModal> | null>(null);

const doApplyTo = (url) => {
    $applyToModal.value?.open(url);
}

const {
    mayNeedRestart: originalMayNeedRestart,
    needsRestart: originalNeedsRestart
} = useMayNeedRestart();

const mayNeedRestart = () => {
    if (!props.useManualAutoDj) {
        return;
    }

    originalMayNeedRestart();
};

const needsRestart = () => {
    if (!props.useManualAutoDj) {
        return;
    }

    originalNeedsRestart();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doModify = (url) => {
    axios.put(url).then((resp) => {
        needsRestart();

        notifySuccess(resp.data.message);
        relist();
    });
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Playlist?'),
    () => {
        relist();
        needsRestart();
    },
);

const {doDelete: doEmpty} = useConfirmAndDelete(
    $gettext('Clear all media from playlist?'),
    () => {
        relist();
        needsRestart();
    },
);
</script>
