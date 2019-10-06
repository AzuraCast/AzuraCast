<template>
    <media-toolbar :selected-files="selectedRows" :csrf="csrf" :current-directory="currentDirectory"
                   @relist="onTriggerRelist"></media-toolbar>

    <data-table ref="datatable" @row-selected="onRowSelected" id="station_media" :fields="fields"
                :api-url="listUrl" :request-config="requestConfig">
        <template v-slot:cell(name)="row">
            <div :class="{ is_dir: row.is_dir, is_file: !row.is_dir }">
                <a :href="row.media_art" class="album-art float-right pl-3" target="_blank">
                    <img style="width: 40px; height: auto; border-radius: 5px;" :alt="lang_album_art"
                         :src="row.media_art">
                </a>
                <template v-if="row.media.is_playable">
                    <a class="file-icon btn-audio" href="#" :data-url="row.media_play_url"
                       @click.prevent="playAudio(row.media_play_url)">
                        <i class="material-icons" aria-hidden="true">play_circle_filled</i>
                    </a>
                </template>
                <template v-else>
                    <span class="file-icon">
                        <i class="material-icons" aria-hidden="true" v-if="row.is_dir">folder</i>
                        <i class="material-icons" aria-hidden="true" v-else>note</i>
                    </span>
                </template>
                <template v-if="row.is_dir">
                    <a class="name" href="#" @click.prevent="changeDirectory(row.path)" :title="row.name">
                        {{ row.text }}
                    </a>
                </template>
                <template v-else>
                    <a class="name" :href="row.media_play_url" target="_blank" :title="row.name">
                        {{ row.media_name }}
                    </a>
                </template>
                <br>
                <small v-if="row.is-dir" v-translate>Directory</small>
                <small v-else>{{ row.text }}</small>
            </div>
        </template>
        <template v-slot:cell(media_length)="row">
            {{ row.media_length_text }}
        </template>
        <template v-slot:cell(size)="row">
            <template v-if="!row.size">&nbsp;</template>
            <template v-else>
                {{ formatFileSize(row.size) }}
            </template>
        </template>
        <template v-slot:cell(playlists)="row">
            <template v-for="(playlist, index) in row.media_playlists">
                <a class="btn-search" href="#" @click.prevent="filter('playlist:'+playlist)">{{ playlist }}</a>
                <span v-if="index+1 < row.media_playlists.length">, </span>
            </template>
        </template>
        <template v-slot:cell(commands)="row">
            <template v-if="row.media_edit_url">
                <a class="btn btn-sm btn-primary" :href=row.media_edit_url v-translate>Edit</a>
            </template>
            <template v-else-if="row.rename_url">
                <a class="btn btn-sm btn-primary" :href=row.rename_url v-translate>Rename</a>
            </template>
            <template v-else>&nbsp;</template>
        </template>
    </data-table>
</template>
<script>
  import DataTable from './components/DataTable'
  import MediaToolbar from './station_media/MediaToolbar'

  import { formatFileSize } from './station_media/utils'

  export default {
    components: { MediaToolbar, DataTable },
    props: {
      listUrl: String,
      batchUrl: String,
      csrf: String
    },
    data () {
      return {
        selectedRows: [],
        currentDirectory: '',
        fields: [
          { key: 'selected', label: '', sortable: false },
          { key: 'name', label: this.$gettext('Name'), sortable: true },
          // { key: 'media_title', label: this.$gettext('Title'), sortable: true },
          // { key: 'media_artist', label: this.$gettext('Artist'), sortable: true },
          // { key: 'media_album', label: this.$gettext('Album'), sortable: true },
          { key: 'media_length', label: this.$gettext('Length'), sortable: true },
          // custom fields
          { key: 'size', label: this.$gettext('Size'), sortable: true },
          {
            key: 'mtime',
            label: this.$gettext('Modified'),
            sortable: true,
            formatter: (value, key, item) => {
              if (!value) {
                return ''
              }
              return moment.unix(value).format('lll')
            }
          },
          { key: 'media_playlists', label: this.$gettext('Playlists'), sortable: true },
          { key: 'commands', label: this.$gettext('Actions'), sortable: false }
        ]
      }
    },
    computed: {
      lang_album_art () {
        return this.$gettext('Album Art')
      }
    },
    methods: {
      formatFileSize (size) {
        return formatFileSize(size)
      },
      onRowSelected (items) {
        this.selectedRows = items
      },
      onTriggerRelist () {
        this.$refs.datatable.list()
      },
      playAudio (url) {
        this.$eventHub.$emit('player_toggle', url)
      },
      changeDirectory (newDir) {
        this.currentDirectory = newDir
      },
      filter (newFilter) {
        this.$refs.datatable.filter(newFilter)
      },
      requestConfig (config) {
        config.params.file = this.currentDirectory
        config.params.csrf = this.csrf

        return config
      }
    }
  }
</script>