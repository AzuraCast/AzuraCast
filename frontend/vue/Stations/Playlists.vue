<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <b-row class="align-items-center">
                    <b-col md="6">
                        <h2 class="card-title" key="lang_playlists" v-translate>Playlists</h2>
                    </b-col>
                    <b-col md="6" class="text-right text-muted">
                        <translate key="lang_station_tz" :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                    </b-col>
                </b-row>
            </b-card-header>
            <b-tabs pills card lazy>
                <b-tab :title="langAllPlaylistsTab" no-body>
                    <b-card-body body-class="card-padding-sm">
                        <b-button variant="outline-primary" @click.prevent="doCreate">
                            <icon icon="add"></icon>
                            <translate key="lang_add_playlist">Add Playlist</translate>
                        </b-button>
                    </b-card-body>

                    <data-table ref="datatable" id="station_playlists" paginated :fields="fields" :responsive="false"
                                :api-url="listUrl">
                        <template v-slot:cell(actions)="row">
                            <b-button-group size="sm">
                                <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                    <translate key="lang_btn_edit">Edit</translate>
                                </b-button>
                                <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                    <translate key="lang_btn_delete">Delete</translate>
                                </b-button>

                                <b-dropdown size="sm" variant="dark" boundary="window" :text="langMore">
                                    <b-dropdown-item @click.prevent="doModify(row.item.links.toggle)">
                                        {{ langToggleButton(row.item) }}
                                    </b-dropdown-item>
                                    <b-dropdown-item @click.prevent="doImport(row.item.links.import)"
                                                     v-if="row.item.source === 'songs'">
                                        {{ langImportButton }}
                                    </b-dropdown-item>
                                    <b-dropdown-item @click.prevent="doReorder(row.item.links.order)"
                                                     v-if="row.item.source === 'songs' && row.item.order === 'sequential'">
                                        {{ langReorderButton }}
                                    </b-dropdown-item>
                                    <b-dropdown-item @click.prevent="doQueue(row.item.links.queue)"
                                                     v-if="row.item.source === 'songs' && row.item.order !== 'random'">
                                        {{ langQueueButton }}
                                    </b-dropdown-item>
                                    <b-dropdown-item @click.prevent="doModify(row.item.links.reshuffle)"
                                                     v-if="row.item.order === 'shuffle'">
                                        {{ langReshuffleButton }}
                                    </b-dropdown-item>
                                    <b-dropdown-item @click.prevent="doClone(row.item.name, row.item.links.clone)">
                                        {{ langCloneButton }}
                                    </b-dropdown-item>
                                    <template v-for="format in ['pls', 'm3u']">
                                        <b-dropdown-item :href="row.item.links.export[format]" target="_blank">
                                            <translate :key="'lang_format_'+format" :translate-params="{ format: format.toUpperCase() }">
                                            Export %{format}
                                            </translate>
                                        </b-dropdown-item>
                                    </template>
                                </b-dropdown>
                            </b-button-group>
                        </template>
                        <template v-slot:cell(name)="row">
                            <h5 class="m-0">{{ row.item.name }}</h5>
                            <div>
                                <span class="badge badge-dark">
                                    <translate key="lang_song_based_playlist" v-if="row.item.source === 'songs'">
                                        Song-based
                                    </translate>
                                    <translate key="lang_remote_url_playlist" v-else>
                                        Remote URL
                                    </translate>
                                </span>
                                <span class="badge badge-primary" v-if="row.item.is_jingle">
                                    <translate key="lang_jingle_mode">Jingle Mode</translate>
                                </span>
                                <span class="badge badge-info"
                                      v-if="row.item.source === 'songs' && row.item.order === 'sequential'">
                                    <translate key="lang_sequential">Sequential</translate>
                                </span>
                                <span class="badge badge-info" v-if="row.item.include_in_on_demand">
                                    <translate key="lang_on_demand">On-Demand</translate>
                                </span>
                                <span class="badge badge-success" v-if="row.item.include_in_automation">
                                    <translate key="lang_auto_assigned">Auto-Assigned</translate>
                                </span>
                                <span class="badge badge-danger" v-if="!row.item.is_enabled">
                                    <translate key="lang_disabled">Disabled</translate>
                                </span>
                            </div>
                        </template>
                        <template v-slot:cell(scheduling)="row">
                            <span v-html="formatType(row.item)"></span>
                        </template>
                        <template v-slot:cell(num_songs)="row">
                            <template v-if="row.item.source === 'songs'">
                                <a :href="filesUrl+'#playlist:'+encodeURIComponent(row.item.name)">
                                    {{ row.item.num_songs }}
                                </a>
                                ({{ formatLength(row.item.total_length) }})
                            </template>
                            <template v-else>&nbsp;</template>
                        </template>
                    </data-table>
                </b-tab>
                <b-tab :title="langScheduleViewTab" no-body>
                    <schedule ref="schedule" :schedule-url="scheduleUrl" :station-time-zone="stationTimeZone"
                              :locale="locale" @click="doCalendarClick"></schedule>
                </b-tab>
            </b-tabs>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
                    :enable-advanced-features="enableAdvancedFeatures" @relist="relist"></edit-modal>
        <reorder-modal ref="reorderModal"></reorder-modal>
        <queue-modal ref="queueModal"></queue-modal>
        <reorder-modal ref="reorderModal"></reorder-modal>
        <import-modal ref="importModal" @relist="relist"></import-modal>
        <clone-modal ref="cloneModal" @relist="relist"></clone-modal>
    </div>
</template>

<script>
import DataTable from '../Common/DataTable';
import Schedule from '../Common/ScheduleView';
import EditModal from './Playlists/EditModal';
import ReorderModal from './Playlists/ReorderModal';
import ImportModal from './Playlists/ImportModal';
import QueueModal from './Playlists/QueueModal';
import axios from 'axios';
import Icon from '../Common/Icon';
import handleAxiosError from '../Function/handleAxiosError';
import CloneModal from './Playlists/CloneModal';

export default {
    name: 'StationPlaylists',
    components: { CloneModal, Icon, QueueModal, ImportModal, ReorderModal, EditModal, Schedule, DataTable },
    props: {
        listUrl: String,
        scheduleUrl: String,
        locale: String,
        filesUrl: String,
        stationTimeZone: String,
        enableAdvancedFeatures: Boolean
    },
    data () {
        return {
            fields: [
                { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                { key: 'name', isRowHeader: true, label: this.$gettext('Playlist'), sortable: false },
                { key: 'scheduling', label: this.$gettext('Scheduling'), sortable: false },
                { key: 'num_songs', label: this.$gettext('# Songs'), sortable: false }
            ]
        };
    },
    computed: {
        langAllPlaylistsTab () {
            return this.$gettext('All Playlists');
        },
        langScheduleViewTab () {
            return this.$gettext('Schedule View');
        },
        langMore () {
            return this.$gettext('More');
        },
        langReorderButton () {
            return this.$gettext('Reorder');
        },
        langQueueButton () {
            return this.$gettext('Playback Queue');
        },
        langReshuffleButton () {
            return this.$gettext('Reshuffle');
        },
        langCloneButton () {
            return this.$gettext('Duplicate');
        },
        langImportButton () {
            return this.$gettext('Import from PLS/M3U');
        }
    },
    mounted () {
        moment.relativeTimeThreshold('ss', 1);
        moment.relativeTimeRounding(function (value) {
            return Math.round(value * 10) / 10;
        });
    },
    methods: {
        langToggleButton (record) {
            return (record.is_enabled)
                ? this.$gettext('Disable')
                : this.$gettext('Enable');
        },
        formatTime (time) {
            return moment(time).tz(this.stationTimeZone).format('LT');
        },
        formatLength (length) {
            return moment.duration(length, 'seconds').humanize();
        },
        formatType (record) {
            if (!record.is_enabled) {
                return this.$gettext('Disabled');
            }

            switch (record.type) {
                case 'default':
                    return this.$gettext('General Rotation') + '<br>' + this.$gettext('Weight') + ': ' + record.weight;

                case 'once_per_x_songs':
                    let oncePerSongs = this.$gettext('Once per %{songs} Songs');
                    return this.$gettextInterpolate(oncePerSongs, { songs: record.play_per_songs });

                case 'once_per_x_minutes':
                    let oncePerMinutes = this.$gettext('Once per %{minutes} Minutes');
                    return this.$gettextInterpolate(oncePerMinutes, { minutes: record.play_per_minutes });

                case 'once_per_hour':
                    let oncePerHour = this.$gettext('Once per Hour (at %{minute})');
                    return this.$gettextInterpolate(oncePerHour, { minute: record.play_per_hour_minute });

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
            notify('<b>' + this.$gettext('Applying changes...') + '</b>', 'warning', {
                delay: 3000
            });

            axios.put(url).then((resp) => {
                notify('<b>' + resp.data.message + '</b>', 'success');

                this.relist();
            }).catch((err) => {
                console.error(err);
                if (err.response.data.message) {
                    notify('<b>' + err.response.data.message + '</b>', 'danger');
                }
            });
        },
        doDelete (url) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete playlist?');

            Swal.fire({
                title: buttonConfirmText,
                confirmButtonText: buttonText,
                confirmButtonColor: '#e64942',
                showCancelButton: true,
                focusCancel: true
            }).then((result) => {
                if (result.value) {
                    axios.delete(url).then((resp) => {
                        notify('<b>' + resp.data.message + '</b>', 'success');

                        this.relist();
                    }).catch((err) => {
                        handleAxiosError(err);
                    });
                }
            });
        }
    }
};
</script>
