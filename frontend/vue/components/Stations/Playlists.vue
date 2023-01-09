<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <b-row class="align-items-center">
                <b-col md="6">
                    <h2 class="card-title">
                        {{ $gettext('Playlists') }}
                    </h2>
                </b-col>
                <b-col
                    md="6"
                    class="text-right text-muted"
                >
                    {{
                        $gettext(
                            'This station\'s time zone is currently %{tz}.',
                            {tz: stationTimeZone}
                        )
                    }}
                </b-col>
            </b-row>
        </b-card-header>
        <b-tabs
            pills
            card
            lazy
        >
            <b-tab
                :title="$gettext('All Playlists')"
                no-body
            >
                <b-card-body body-class="card-padding-sm">
                    <b-button
                        variant="outline-primary"
                        @click.prevent="doCreate"
                    >
                        <icon icon="add" />
                        {{ $gettext('Add Playlist') }}
                    </b-button>
                </b-card-body>

                <data-table
                    id="station_playlists"
                    ref="$datatable"
                    paginated
                    :fields="fields"
                    :responsive="false"
                    :api-url="listUrl"
                >
                    <template #cell(actions)="row">
                        <b-button-group size="sm">
                            <b-button
                                size="sm"
                                variant="primary"
                                @click.prevent="doEdit(row.item.links.self)"
                            >
                                {{ $gettext('Edit') }}
                            </b-button>
                            <b-button
                                size="sm"
                                variant="danger"
                                @click.prevent="doDelete(row.item.links.self)"
                            >
                                {{ $gettext('Delete') }}
                            </b-button>

                            <b-dropdown
                                size="sm"
                                variant="dark"
                                boundary="window"
                                :text="$gettext('More')"
                            >
                                <b-dropdown-item @click.prevent="doModify(row.item.links.toggle)">
                                    {{ langToggleButton(row.item) }}
                                </b-dropdown-item>
                                <b-dropdown-item
                                    v-if="row.item.source === 'songs'"
                                    @click.prevent="doImport(row.item.links.import)"
                                >
                                    {{ $gettext('Import from PLS/M3U') }}
                                </b-dropdown-item>
                                <b-dropdown-item
                                    v-if="row.item.source === 'songs' && row.item.order === 'sequential'"
                                    @click.prevent="doReorder(row.item.links.order)"
                                >
                                    {{ $gettext('Reorder') }}
                                </b-dropdown-item>
                                <b-dropdown-item
                                    v-if="row.item.source === 'songs' && row.item.order !== 'random'"
                                    @click.prevent="doQueue(row.item.links.queue)"
                                >
                                    {{ $gettext('Playback Queue') }}
                                </b-dropdown-item>
                                <b-dropdown-item
                                    v-if="row.item.order === 'shuffle'"
                                    @click.prevent="doModify(row.item.links.reshuffle)"
                                >
                                    {{ $gettext('Reshuffle') }}
                                </b-dropdown-item>
                                <b-dropdown-item @click.prevent="doClone(row.item.name, row.item.links.clone)">
                                    {{ $gettext('Duplicate') }}
                                </b-dropdown-item>
                                <template
                                    v-for="format in ['pls', 'm3u']"
                                    :key="format"
                                >
                                    <b-dropdown-item
                                        :href="row.item.links.export[format]"
                                        target="_blank"
                                    >
                                        {{
                                            $gettext(
                                                'Export %{format}',
                                                {format: format.toUpperCase()}
                                            )
                                        }}
                                    </b-dropdown-item>
                                </template>
                            </b-dropdown>
                        </b-button-group>
                    </template>
                    <template #cell(name)="row">
                        <h5 class="m-0">
                            {{ row.item.name }}
                        </h5>
                        <div>
                            <span class="badge badge-dark">
                                <template v-if="row.item.source === 'songs'">
                                    {{ $gettext('Song-based') }}
                                </template>
                                <template v-else>
                                    {{ $gettext('Remote URL') }}
                                </template>
                            </span>
                            <span
                                v-if="row.item.is_jingle"
                                class="badge badge-primary"
                            >
                                {{ $gettext('Jingle Mode') }}
                            </span>
                            <span
                                v-if="row.item.source === 'songs' && row.item.order === 'sequential'"
                                class="badge badge-info"
                            >
                                {{ $gettext('Sequential') }}
                            </span>
                            <span
                                v-if="row.item.include_in_on_demand"
                                class="badge badge-info"
                            >
                                {{ $gettext('On-Demand') }}
                            </span>
                            <span
                                v-if="row.item.include_in_automation"
                                class="badge badge-success"
                            >
                                {{ $gettext('Auto-Assigned') }}
                            </span>
                            <span
                                v-if="!row.item.is_enabled"
                                class="badge badge-danger"
                            >
                                {{ $gettext('Disabled') }}
                            </span>
                        </div>
                    </template>
                    <template #cell(scheduling)="row">
                        <span v-html="formatType(row.item)" />
                    </template>
                    <template #cell(num_songs)="row">
                        <template v-if="row.item.source === 'songs'">
                            <a :href="filesUrl+'#playlist:'+encodeURIComponent(row.item.name)">
                                {{ row.item.num_songs }}
                            </a>
                            ({{ formatLength(row.item.total_length) }})
                        </template>
                        <template v-else>
&nbsp;
                        </template>
                    </template>
                </data-table>
            </b-tab>
            <b-tab
                :title="$gettext('Schedule View')"
                no-body
            >
                <schedule
                    ref="$schedule"
                    :schedule-url="scheduleUrl"
                    :station-time-zone="stationTimeZone"
                    @click="doCalendarClick"
                />
            </b-tab>
        </b-tabs>
    </b-card>

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
import humanizeDuration from 'humanize-duration';
import {useAzuraCast} from "~/vendor/azuracast";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import {mayNeedRestartProps, useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import confirmAndDelete from "~/functions/confirmAndDelete";

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

const {localeShort} = useAzuraCast();

const formatLength = (length) => humanizeDuration(
    length * 1000,
    {
        round: true,
        language: localeShort,
        fallbacks: ['en']
    }
);

const formatType = (record) => {
    if (!record.is_enabled) {
        return $gettext('Disabled');
    }

    switch (record.type) {
        case 'default':
            return $gettext('General Rotation') + '<br>' + $gettext('Weight') + ': ' + record.weight;

        case 'once_per_x_songs':
            return $gettext(
                'Once per %{songs} Songs',
                {songs: record.play_per_songs}
            );

        case 'once_per_x_minutes':
            return $gettext(
                'Once per %{minutes} Minutes',
                {minutes: record.play_per_minutes}
            );

        case 'once_per_hour':
            return $gettext(
                'Once per Hour (at %{minute})',
                {minute: record.play_per_hour_minute}
            );

        default:
            return $gettext('Custom');
    }
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

const {
    mayNeedRestart: originalMayNeedRestart,
    needsRestart: originalNeedsRestart
} = useMayNeedRestart(props.restartStatusUrl);

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

const doDelete = (url) => confirmAndDelete(
    url,
    $gettext('Delete Playlist?'),
    () => {
        relist();
        needsRestart();
    },
);
</script>
