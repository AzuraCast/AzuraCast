<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <div class="flex-shrink">
                    <h2 class="card-title py-2">
                        <translate>On-Demand Media</translate>
                    </h2>
                </div>
                <div class="flex-fill text-right">
                    <inline-player ref="player"></inline-player>
                </div>
            </div>
        </div>

        <div class="table-responsive table-responsive-lg">
            <data-table ref="datatable" class="on_demand_table" paginated select-fields
                        :fields="fields" :api-url="listUrl">
                <template v-slot:cell(download_url)="row">
                    <a class="file-icon btn-audio" href="#" :data-url="row.item.download_url"
                       @click.prevent="playAudio(row.item.download_url)" :title="langPlayPause">
                        <i class="material-icons" aria-hidden="true" v-if="now_playing_url === row.item.download_url">pause_circle_filled</i>
                        <i class="material-icons" aria-hidden="true" v-else>play_circle_filled</i>
                    </a>
                    &nbsp;
                    <a class="name" :href="row.item.download_url" target="_blank" :title="langDownload">
                        <i class="material-icons">cloud_download</i>
                    </a>
                </template>
                <template v-slot:cell(media_art)="row">
                    <a :href="row.item.media_art" class="album-art" target="_blank"
                       data-fancybox="gallery">
                        <img class="media_manager_album_art" :alt="langAlbumArt" :src="row.item.media_art">
                    </a>
                </template>
                <template v-slot:cell(size)="row">
                    <template v-if="!row.item.size">&nbsp;</template>
                    <template v-else>
                        {{ formatFileSize(row.item.size) }}
                    </template>
                </template>
            </data-table>
        </div>
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
    import InlinePlayer from './InlinePlayer';
    import DataTable from './components/DataTable';
    import _ from 'lodash';

    export default {
        components: { DataTable, InlinePlayer },
        props: {
            listUrl: String,
            customFields: Array
        },
        data () {
            let fields = [
                { key: 'download_url', label: ' ', sortable: false, selectable: false },
                { key: 'media_art', label: this.$gettext('Art'), sortable: false, selectable: true },
                { key: 'media_title', label: this.$gettext('Title'), sortable: true, selectable: true },
                {
                    key: 'media_artist',
                    label: this.$gettext('Artist'),
                    sortable: true,
                    selectable: true
                },
                { key: 'media_album', label: this.$gettext('Album'), sortable: true, selectable: true }
            ];

            _.forEach(this.customFields.slice(), (field) => {
                fields.push({
                    key: field.display_key,
                    label: field.label,
                    sortable: true,
                    selectable: true,
                    visible: false
                });
            });

            return {
                now_playing_url: '',
                fields: fields
            };
        },
        mounted () {
            this.$eventHub.$on('player_stopped', () => {
                this.now_playing_url = '';
            });

            this.$eventHub.$on('player_playing', (url) => {
                this.now_playing_url = url;
            });
        },
        computed: {
            langAlbumArt () {
                return this.$gettext('Album Art');
            },
            langPlayPause () {
                return this.$gettext('Play/Pause');
            },
            langDownload () {
                return this.$gettext('Download');
            }
        },
        methods: {
            playAudio (url) {
                if (this.now_playing_url === url) {
                    this.$refs.player.stop();
                } else {
                    this.$refs.player.play(url);
                }
            }
        }
    };
</script>