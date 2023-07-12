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
                    {{
                        $gettext(
                            'This station\'s time zone is currently %{tz}.',
                            {tz: stationTimeZone}
                        )
                    }}
                </div>
            </div>
        </div>

        <div class="card-body">
            <o-tabs
                nav-tabs-class="nav-tabs"
                content-class="mt-3"
                destroy-on-hide
            >
                <o-tab-item :label="$gettext('All Playlists')">
                    <div class="card-body-flush">
                        <div class="card-body buttons">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="doCreate"
                            >
                                <icon icon="add" />
                                <span>
                                    {{ $gettext('Add Playlist') }}
                                </span>
                            </button>
                        </div>

                        <data-table
                            id="station_playlists"
                            ref="$datatable"
                            paginated
                            :fields="fields"
                            :responsive="false"
                            :api-url="listUrl"
                        >
                            <template #cell(actions)="row">
                                <div class="btn-group btn-group-sm">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="doEdit(row.item.links.self)"
                                    >
                                        {{ $gettext('Edit') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        @click="doDelete(row.item.links.self)"
                                    >
                                        {{ $gettext('Delete') }}
                                    </button>

                                    <div class="dropdown btn-group">
                                        <button
                                            class="btn btn-sm btn-secondary dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                            {{ $gettext('More') }}
                                            <span class="caret" />
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doModify(row.item.links.toggle)"
                                                >
                                                    {{ langToggleButton(row.item) }}
                                                </button>
                                            </li>
                                            <li v-if="row.item.links.reshuffle">
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doModify(row.item.links.reshuffle)"
                                                >
                                                    {{ $gettext('Reshuffle') }}
                                                </button>
                                            </li>
                                            <li v-if="row.item.links.import">
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doImport(row.item.links.import)"
                                                >
                                                    {{ $gettext('Import from PLS/M3U') }}
                                                </button>
                                            </li>
                                            <li v-if="row.item.links.order">
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doReorder(row.item.links.order)"
                                                >
                                                    {{ $gettext('Reorder') }}
                                                </button>
                                            </li>
                                            <li v-if="row.item.links.queue">
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doQueue(row.item.links.queue)"
                                                >
                                                    {{ $gettext('Playback Queue') }}
                                                </button>
                                            </li>
                                            <li v-if="row.item.links.applyto">
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doApplyTo(row.item.links.applyto)"
                                                >
                                                    {{ $gettext('Apply to Folders') }}
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item"
                                                    @click="doClone(row.item.name, row.item.links.clone)"
                                                >
                                                    {{ $gettext('Duplicate') }}
                                                </button>
                                            </li>
                                            <li
                                                v-for="format in ['pls', 'm3u']"
                                                :key="format"
                                            >
                                                <a
                                                    class="dropdown-item"
                                                    :href="row.item.links.export[format]"
                                                    target="_blank"
                                                >
                                                    {{
                                                        $gettext(
                                                            'Export %{format}',
                                                            {format: format.toUpperCase()}
                                                        )
                                                    }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
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
                                    <a :href="filesUrl+'#playlist:'+encodeURIComponent(row.item.short_name)">
                                        {{ row.item.num_songs }}
                                    </a>
                                    ({{ formatLength(row.item.total_length) }})
                                </template>
                                <template v-else>
                                    &nbsp;
                                </template>
                            </template>
                        </data-table>
                    </div>
                </o-tab-item>
                <o-tab-item :label="$gettext('Schedule View')">
                    <div class="card-body-flush">
                        <schedule
                            ref="$schedule"
                            :schedule-url="scheduleUrl"
                            :station-time-zone="stationTimeZone"
                            @click="doCalendarClick"
                        />
                    </div>
                </o-tab-item>
            </o-tabs>
        </div>
    </section>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :station-time-zone="stationTimeZone"
        :enable-advanced-features="enableAdvancedFeatures"
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

<script setup>
import DataTable from '~/components/Common/DataTable';
import Schedule from '~/components/Common/ScheduleView';
import EditModal from './Playlists/EditModal';
import ReorderModal from './Playlists/ReorderModal';
import ImportModal from './Playlists/ImportModal';
import QueueModal from './Playlists/QueueModal';
import Icon from '~/components/Common/Icon';
import CloneModal from './Playlists/CloneModal';
import ApplyToModal from "./Playlists/ApplyToModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import {mayNeedRestartProps, useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import {Duration} from "luxon";

const props = defineProps({
    ...mayNeedRestartProps,
    listUrl: {
        type: String,
        required: true
    },
    scheduleUrl: {
        type: String,
        required: true
    },
    filesUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    },
    useManualAutoDj: {
        type: Boolean,
        required: true
    },
    enableAdvancedFeatures: {
        type: Boolean,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Playlist'), sortable: true},
    {key: 'scheduling', label: $gettext('Scheduling'), sortable: false},
    {key: 'num_songs', label: $gettext('# Songs'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const langToggleButton = (record) => {
    return (record.is_enabled)
        ? $gettext('Disable')
        : $gettext('Enable');
};

const formatLength = (length) => {
    if (0 === length) {
        return $gettext('None');
    }

    const duration = Duration.fromMillis(length * 1000);
    return duration.rescale().toHuman();
};

const $datatable = ref(); // Template Ref
const $schedule = ref(); // Template Ref

const relist = () => {
    $datatable.value?.refresh();
    $schedule.value?.refresh();
};

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event) => {
    doEdit(event.extendedProps.edit_url);
};

const $reorderModal = ref(); // Template Ref

const doReorder = (url) => {
    $reorderModal.value?.open(url);
};

const $queueModal = ref(); // Template Ref

const doQueue = (url) => {
    $queueModal.value?.open(url);
};

const $importModal = ref(); // Template Ref

const doImport = (url) => {
    $importModal.value?.open(url);
};

const $cloneModal = ref(); // Template Ref

const doClone = (name, url) => {
    $cloneModal.value?.open(name, url);
};

const $applyToModal = ref(); // Template Ref

const doApplyTo = (url) => {
    $applyToModal.value?.open(url);
}

const {
    mayNeedRestart: originalMayNeedRestart,
    needsRestart: originalNeedsRestart
} = useMayNeedRestart(props);

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

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doModify = (url) => {
    wrapWithLoading(
        axios.put(url)
    ).then((resp) => {
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
</script>
