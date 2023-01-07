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
                    ref="datatable"
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
                    ref="schedule"
                    :schedule-url="scheduleUrl"
                    :station-time-zone="stationTimeZone"
                    @click="doCalendarClick"
                />
            </b-tab>
        </b-tabs>
    </b-card>

    <edit-modal
        ref="editModal"
        :create-url="listUrl"
        :station-time-zone="stationTimeZone"
        :enable-advanced-features="enableAdvancedFeatures"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
    <reorder-modal ref="reorderModal" />
    <queue-modal ref="queueModal" />
    <reorder-modal ref="reorderModal" />
    <import-modal
        ref="importModal"
        @relist="relist"
    />
    <clone-modal
        ref="cloneModal"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import Schedule from '~/components/Common/ScheduleView';
import EditModal from './Playlists/EditModal';
import ReorderModal from './Playlists/ReorderModal';
import ImportModal from './Playlists/ImportModal';
import QueueModal from './Playlists/QueueModal';
import Icon from '~/components/Common/Icon';
import CloneModal from './Playlists/CloneModal';
import {DateTime} from 'luxon';
import humanizeDuration from 'humanize-duration';
import {useAzuraCast} from "~/vendor/azuracast";

/* TODO Options API */

export default {
    name: 'StationPlaylists',
    components: {CloneModal, Icon, QueueModal, ImportModal, ReorderModal, EditModal, Schedule, DataTable},
    props: {
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
        restartStatusUrl: {
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
    },
    data () {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Playlist'), sortable: true},
                {key: 'scheduling', label: this.$gettext('Scheduling'), sortable: false},
                {key: 'num_songs', label: this.$gettext('# Songs'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        langToggleButton (record) {
            return (record.is_enabled)
                ? this.$gettext('Disable')
                : this.$gettext('Enable');
        },
        formatTime (time) {
            const {timeConfig} = useAzuraCast();

            return DateTime.fromSeconds(time).setZone(this.stationTimeZone).toLocaleString(
                {...DateTime.DATETIME_MED, ...timeConfig}
            );
        },
        formatLength (length) {
            const {localeShort} = useAzuraCast();

            return humanizeDuration(length * 1000, {
                round: true,
                language: localeShort,
                fallbacks: ['en']
            });
        },
        formatType (record) {
            if (!record.is_enabled) {
                return this.$gettext('Disabled');
            }

            switch (record.type) {
                case 'default':
                    return this.$gettext('General Rotation') + '<br>' + this.$gettext('Weight') + ': ' + record.weight;

                case 'once_per_x_songs':
                    return this.$gettext(
                        'Once per %{songs} Songs',
                        {songs: record.play_per_songs}
                    );

                case 'once_per_x_minutes':
                    return this.$gettext(
                        'Once per %{minutes} Minutes',
                        {minutes: record.play_per_minutes}
                    );

                case 'once_per_hour':
                    return this.$gettext(
                        'Once per Hour (at %{minute})',
                        {minute: record.play_per_hour_minute}
                    );

                default:
                    return this.$gettext('Custom');
            }
        },
        relist () {
            if (this.$refs.datatable) {
                this.$refs.datatable.refresh();
            }
            if (this.$refs.schedule) {
                this.$refs.schedule.refresh();
            }
        },
        doCreate () {
            this.$refs.editModal.create();
        },
        doCalendarClick (event) {
            this.doEdit(event.extendedProps.edit_url);
        },
        doEdit (url) {
            this.$refs.editModal.edit(url);
        },
        doReorder (url) {
            this.$refs.reorderModal.open(url);
        },
        doQueue (url) {
            this.$refs.queueModal.open(url);
        },
        doImport (url) {
            this.$refs.importModal.open(url);
        },
        doClone (name, url) {
            this.$refs.cloneModal.open(name, url);
        },
        doModify (url) {
            this.$wrapWithLoading(
                this.axios.put(url)
            ).then((resp) => {
                this.needsRestart();

                this.$notifySuccess(resp.data.message);
                this.relist();
            });
        },
        doDelete (url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Playlist?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.needsRestart();

                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        },
        mayNeedRestart() {
            if (!this.useManualAutoDj) {
                return;
            }

            this.axios.get(this.restartStatusUrl).then((resp) => {
                if (resp.data.needs_restart) {
                    this.needsRestart();
                }
            });
        },
        needsRestart() {
            if (!this.useManualAutoDj) {
                return;
            }

            document.dispatchEvent(new CustomEvent("station-needs-restart"));
        }
    }
};
</script>
