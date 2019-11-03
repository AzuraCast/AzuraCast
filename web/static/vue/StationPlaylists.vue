<template>
    <b-card no-body>
        <b-card-header>
            <b-row>
                <b-col md="6">
                    <h2 class="card-title" v-translate>Playlists</h2>
                </b-col>
                <b-col md="6">
                    <translate :translate-params="{ tz: stationTimeZone }">
                        This station's time zone is currently %{tz}.
                    </translate>
                </b-col>
            </b-row>
        </b-card-header>
        <b-tabs pills card>
            <b-tab :title="langAllPlaylistsTab">
                <a class="btn btn-outline-primary" role="button" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate>Add Playlist</translate>
                </a>

                <div class="table-responsive table-responsive-lg">
                    <data-table ref="datatable" id="station_playlists" paginated :fields="fields" :api-url="listUrl">
                        <template v-slot:cell(actions)="row">
                            <b-button-group size="sm">
                                <b-button size="sm" variant="primary" @click.prevent="doEdit" v-translate>
                                    Edit
                                </b-button>
                                <b-button size="sm" variant="danger" @click.prevent="doDelete" v-translate>
                                    Delete
                                </b-button>

                                <b-dropdown size="sm" variant="dark" :text="langMore">
                                    <b-dropdown-item @click.prevent="doToggle">
                                        <translate v-if="row.item.is_enabled">Disable</translate>
                                        <translate v-else>Enable</translate>
                                    </b-dropdown-item>
                                    <b-dropdown-item
                                            v-if="row.item.source === 'songs' && row.item.order === 'sequential'"
                                            @click.prevent="doReorder" v-translate>
                                        Reorder
                                    </b-dropdown-item>
                                    <template v-for="format in ['PLS', 'M3U']">
                                        <b-dropdown-item @click="doExport(format)">
                                            <translate :translate-params="{ format: format }">Export %{format}
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
                            <template v-if="!row.item.is_enabled">
                                <translate>Disabled</translate>
                            </template>
                            <template v-else-if="row.item.type === 'default'">
                                <translate>General Rotation</translate>
                                <br>
                                <translate>Weight</translate>
                                : {{ row.item.weight }} ({{ row.item.probability }})
                            </template>
                            <template v-else-if="row.item.type === 'once_per_x_songs'">
                                <translate :translate-params="{ songs: row.item.play_per_songs }">
                                    Once per %{songs} Songs
                                </translate>
                            </template>
                            <template v-else-if="row.item.type === 'once_per_x_minutes'">
                                <translate :translate-params="{ minutes: row.item.play_per_minutes }">
                                    Once per %{minutes} Minutes
                                </translate>
                            </template>
                            <template v-else-if="row.item.type === 'once_per_hour'">
                                <translate :translate-params="{ minute: row.item.play_per_hour_minute }">
                                    Once per hour (at %{minute})
                                </translate>
                            </template>
                            <template v-else>
                                <translate>Custom</translate>
                            </template>
                        </template>
                        <template v-slot:cell(num_songs)="row">
                            <a :href="this.filesUrl+'#playlist:'+encodeURIComponent(row.item.name)">
                                {{ row.item.num_songs }}
                            </a>
                            ( {{ formatLength(row.item.total_length) }} )
                        </template>
                    </data-table>
                </div>
            </b-tab>
            <b-tab :title="langScheduleViewTab">
                <calendar :calendar-url="calendarUrl" :station-time-zone="stationTimeZone" :locale="locale"></calendar>
            </b-tab>
        </b-tabs>
    </b-card>
</template>

<script>
  import DataTable from './components/DataTable.vue'
  import Calendar from './station_playlists/Calendar.vue'

  export default {
    name: 'StationPlaylists',
    components: { Calendar, DataTable },
    props: {
      listUrl: String,
      calendarUrl: String,
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
      }
    },
    mounted () {
      moment.relativeTimeThreshold('ss', 1)
      moment.relativeTimeRounding(function (value) {
        return Math.round(value * 10) / 10
      })
    },
    methods: {
      formatTime (time) {
        return moment(time).tz(this.stationTimeZone).format('LT')
      },
      formatLength (length) {
        return moment.duration(length, 'seconds').humanize()
      },
      doCreate () {

      },
      doEdit () {

      },
      doReorder () {

      },
      doToggle () {

      },
      doExport () {

      },
      doDelete () {

      }
    }
  }
</script>

<style lang="scss">

</style>