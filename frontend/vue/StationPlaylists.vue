<template>
    <div>
        <b-card no-body>
            <b-card-header>
                <b-row class="align-items-center">
                    <b-col md="6">
                        <h2 class="card-title" v-translate>Playlists</h2>
                    </b-col>
                    <b-col md="6" class="text-right text-muted">
                        <translate :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                    </b-col>
                </b-row>
            </b-card-header>
            <b-tabs pills card lazy>
                <b-tab :title="langAllPlaylistsTab" no-body>
                    <b-card-body body-class="card-padding-sm">
                        <b-button variant="outline-primary" @click.prevent="doCreate">
                            <i class="material-icons" aria-hidden="true">add</i>
                            <translate>Add Playlist</translate>
                        </b-button>
                    </b-card-body>

                    <div class="table-responsive table-responsive-lg">
                        <data-table ref="datatable" id="station_playlists" paginated :fields="fields"
                                    :api-url="listUrl">
                            <template v-slot:cell(actions)="row">
                                <b-button-group size="sm">
                                    <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                        <translate>Edit</translate>
                                    </b-button>
                                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                        <translate>Delete</translate>
                                    </b-button>

                                    <b-dropdown size="sm" variant="dark" :text="langMore">
                                        <b-dropdown-item @click.prevent="doToggle(row.item.links.toggle)">
                                            {{ langToggleButton(row.item) }}
                                        </b-dropdown-item>
                                        <b-dropdown-item @click.prevent="doReorder(row.item.links.order)"
                                                         v-if="row.item.source === 'songs' && row.item.order === 'sequential'">
                                            {{ langReorderButton }}
                                        </b-dropdown-item>
                                        <template v-for="format in ['pls', 'm3u']">
                                            <b-dropdown-item :href="row.item.links.export[format]" target="_blank">
                                                <translate :translate-params="{ format: format.toUpperCase() }">
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
                                        <translate v-if="row.item.source === 'songs'">
                                            Song-based
                                        </translate>
                                        <translate v-else>
                                            Remote URL
                                        </translate>
                                    </span>
                                    <span class="badge badge-primary" v-if="row.item.is_jingle">
                                        <translate>Jingle Mode</translate>
                                    </span>
                                    <span class="badge badge-info"
                                          v-if="row.item.source === 'songs' && row.item.order === 'sequential'">
                                        <translate>Sequential</translate>
                                    </span>
                                    <span class="badge badge-success" v-if="row.item.include_in_automation">
                                        <translate>Auto-Assigned</translate>
                                    </span>
                                </div>
                            </template>
                            <template v-slot:cell(scheduling)="row">
                                <span v-html="formatType(row.item)"></span>
                            </template>
                            <template v-slot:cell(num_songs)="row">
                                <a :href="filesUrl+'#playlist:'+encodeURIComponent(row.item.name)">
                                    {{ row.item.num_songs }}
                                </a>
                                ({{ formatLength(row.item.total_length) }})
                            </template>
                        </data-table>
                    </div>
                </b-tab>
                <b-tab :title="langScheduleViewTab" no-body>
                    <schedule ref="schedule" :schedule-url="scheduleUrl" :station-time-zone="stationTimeZone"
                              :locale="locale" @edit="doEdit"></schedule>
                </b-tab>
            </b-tabs>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
                    @relist="relist"></edit-modal>
        <reorder-modal ref="reorderModal"></reorder-modal>
    </div>
</template>

<script>
    import DataTable from './components/DataTable'
    import Schedule from './station_playlists/PlaylistSchedule'
    import EditModal from './station_playlists/PlaylistEditModal'
    import ReorderModal from './station_playlists/PlaylistReorderModal'
    import axios from 'axios'

    export default {
        name: 'StationPlaylists',
        components: { ReorderModal, EditModal, Schedule, DataTable },
        props: {
            listUrl: String,
            scheduleUrl: String,
            locale: String,
            filesUrl: String,
            stationTimeZone: String
        },
        data () {
            return {
                fields: [
                    { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                    { key: 'name', label: this.$gettext('Playlist'), sortable: false },
                    { key: 'scheduling', label: this.$gettext('Scheduling'), sortable: false },
                    { key: 'num_songs', label: this.$gettext('# Songs'), sortable: false }
                ]
            }
        },
        computed: {
            langAllPlaylistsTab () {
                return this.$gettext('All Playlists')
            },
            langScheduleViewTab () {
                return this.$gettext('Schedule View')
            },
            langMore () {
                return this.$gettext('More')
            },
            langReorderButton () {
                return this.$gettext('Reorder')
            }
        },
        mounted () {
            moment.relativeTimeThreshold('ss', 1)
            moment.relativeTimeRounding(function (value) {
                return Math.round(value * 10) / 10
            })
        },
        methods: {
            langToggleButton (record) {
                return (record.is_enabled)
                        ? this.$gettext('Disable')
                        : this.$gettext('Enable')
            },
            formatTime (time) {
                return moment(time).tz(this.stationTimeZone).format('LT')
            },
            formatLength (length) {
                return moment.duration(length, 'seconds').humanize()
            },
            formatType (record) {
                if (!record.is_enabled) {
                    return this.$gettext('Disabled')
                }

                switch (record.type) {
                    case 'default':
                        return this.$gettext('General Rotation') + '<br>' + this.$gettext('Weight') + ': ' + record.weight

                    case 'once_per_x_songs':
                        let oncePerSongs = this.$gettext('Once per %{songs} Songs')
                        return this.$gettextInterpolate(oncePerSongs, { songs: record.play_per_songs })

                    case 'once_per_x_minutes':
                        let oncePerMinutes = this.$gettext('Once per %{minutes} Minutes')
                        return this.$gettextInterpolate(oncePerMinutes, { minutes: record.play_per_minutes })

                    case 'once_per_hour':
                        let oncePerHour = this.$gettext('Once per Hour (at %{minute})')
                        return this.$gettextInterpolate(oncePerHour, { minute: record.play_per_hour_minute })

                    default:
                        return this.$gettext('Custom')
                }
            },
            relist () {
                if (this.$refs.datatable) {
                    this.$refs.datatable.refresh()
                }
                if (this.$refs.schedule) {
                    this.$refs.schedule.refresh()
                }
            },
            doCreate () {
                this.$refs.editModal.create()
            },
            doEdit (url) {
                this.$refs.editModal.edit(url)
            },
            doReorder (url) {
                this.$refs.reorderModal.open(url)
            },
            doToggle (url) {
                notify('<b>' + this.$gettext('Applying changes...') + '</b>', 'warning', {
                    delay: 3000
                })

                axios.put(url).then((resp) => {
                    notify('<b>' + resp.data.message + '</b>', 'success')

                    this.relist()
                }).catch((err) => {
                    console.error(err)
                    if (err.response.message) {
                        notify('<b>' + err.response.message + '</b>', 'danger')
                    }
                })
            },
            doDelete (url) {
                let buttonText = this.$gettext('Delete')
                let buttonConfirmText = this.$gettext('Delete playlist?')

                swal({
                    title: buttonConfirmText,
                    buttons: [true, buttonText],
                    dangerMode: true
                }).then((value) => {
                    if (value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success')

                            this.relist()
                        }).catch((err) => {
                            console.error(err)
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger')
                            }
                        })
                    }
                })
            }
        }
    }
</script>