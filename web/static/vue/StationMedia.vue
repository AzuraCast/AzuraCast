<template>
    <div>
        <div class="card-body">
            <breadcrumb :current-directory="currentDirectory" @change-directory="changeDirectory"></breadcrumb>

            <file-upload :csrf="csrf" :upload-url="uploadUrl" :search-phrase="searchPhrase"
                         @relist="onTriggerRelist"></file-upload>

            <media-toolbar :selected-files="selectedFiles" :csrf="csrf" :current-directory="currentDirectory"
                           @relist="onTriggerRelist" @filtered="onFiltered"></media-toolbar>
        </div>

        <div class="table-responsive table-responsive-lg">
            <data-table ref="datatable" selectable @row-selected="onRowSelected" id="station_media" :fields="fields"
                        :api-url="listUrl" :request-config="requestConfig">
                <template v-slot:cell(name)="row">
                    <div :class="{ is_dir: row.item.is_dir, is_file: !row.item.is_dir }">
                        <a :href="row.item.media_art" class="album-art float-right pl-3" target="_blank"
                           v-if="row.item.media_art">
                            <img class="media_manager_album_art" :alt="lang_album_art" :src="row.item.media_art">
                        </a>
                        <template v-if="row.item.media_is_playable">
                            <a class="file-icon btn-audio" href="#" :data-url="row.item.media_play_url"
                               @click.prevent="playAudio(row.item.media_play_url)">
                                <i class="material-icons" aria-hidden="true">play_circle_filled</i>
                            </a>
                        </template>
                        <template v-else>
                            <span class="file-icon">
                                <i class="material-icons" aria-hidden="true" v-if="row.item.is_dir">folder</i>
                                <i class="material-icons" aria-hidden="true" v-else>note</i>
                            </span>
                        </template>
                        <template v-if="row.item.is_dir">
                            <a class="name" href="#" @click.prevent="changeDirectory(row.item.path)"
                               :title="row.item.name">
                                {{ row.item.text }}
                            </a>
                        </template>
                        <template v-else>
                            <a class="name" :href="row.item.media_play_url" target="_blank" :title="row.item.name">
                                {{ row.item.media_name }}
                            </a>
                        </template>
                        <br>
                        <small v-if="row.item.is_dir" v-translate>Directory</small>
                        <small v-else>{{ row.item.text }}</small>
                    </div>
                </template>
                <template v-slot:cell(media_length)="row">
                    {{ row.item.media_length_text }}
                </template>
                <template v-slot:cell(size)="row">
                    <template v-if="!row.value">&nbsp;</template>
                    <template v-else>
                        {{ formatFileSize(row.value) }}
                    </template>
                </template>
                <template v-slot:cell(playlists)="row">
                    <template v-for="(index, playlist) in row.item.media_playlists">
                        <a class="btn-search" href="#" @click.prevent="filter('playlist:'+playlist)">{{ playlist }}</a>
                        <span v-if="index+1 < row.media_playlists.length">, </span>
                    </template>
                </template>
                <template v-slot:cell(commands)="row">
                    <template v-if="row.item.media_edit_url">
                        <a class="btn btn-sm btn-primary" :href="row.item.media_edit_url" v-translate>Edit</a>
                    </template>
                    <template v-else-if="row.item.rename_url">
                        <a class="btn btn-sm btn-primary" :href="row.item.rename_url" v-translate>Rename</a>
                    </template>
                    <template v-else>&nbsp;</template>
                </template>
            </data-table>
        </div>

        <new-directory-modal :current-directory="currentDirectory" :mkdir-url="mkdirUrl" :csrf="csrf"
                             @relist="onTriggerRelist">
        </new-directory-modal>

        <move-files-modal :selected-files="selectedFiles" :current-directory="currentDirectory" :batch-url="batchUrl"
                          :list-directories-url="listDirectoriesUrl" :csrf="csrf" @relist="onTriggerRelist">
        </move-files-modal>
    </div>
</template>

<style lang="scss">
    img.media_manager_album_art {
        width: 40px;
        height: auto;
        border-radius: 5px;
    }
</style>

<script>
  import DataTable from './components/DataTable.vue'
  import MediaToolbar from './station_media/MediaToolbar.vue'
  import Breadcrumb from './station_media/Breadcrumb.vue'
  import FileUpload from './station_media/FileUpload.vue'
  import NewDirectoryModal from './station_media/NewDirectoryModal.vue'
  import MoveFilesModal from './station_media/MoveFilesModal.vue'
  import { formatFileSize } from './station_media/utils'

  export default {
    components: { MoveFilesModal, NewDirectoryModal, FileUpload, MediaToolbar, DataTable, Breadcrumb },
    props: {
      csrf: String,
      listUrl: String,
      batchUrl: String,
      uploadUrl: String,
      listDirectoriesUrl: String,
      mkdirUrl: String
    },
    data () {
      return {
        selectedFiles: [],
        currentDirectory: '',
        searchPhrase: null,
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
          { key: 'playlists', label: this.$gettext('Playlists'), sortable: true },
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
        this.selectedFiles = items
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
        this.$refs.datatable.setFilter(newFilter)
      },
      onFiltered (newFilter) {
        this.searchPhrase = newFilter
      },
      requestConfig (config) {
        config.params.file = this.currentDirectory
        config.params.csrf = this.csrf

        return config
      }
    }
  }
</script>