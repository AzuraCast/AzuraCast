<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <b-row class="align-items-center">
                    <b-col md="6">
                        <h2 class="card-title" key="lang_episodes" v-translate>Episodes</h2>
                        <h4 class="card-subtitle text-muted">{{ podcast.title }}</h4>
                    </b-col>
                </b-row>
            </b-card-header>

            <b-tabs pills card lazy>
                <b-tab :title="langAllEpisodesTab" no-body>
                    <b-card-body body-class="card-padding-sm">
                        <b-button variant="outline-primary" @click.prevent="doCreate">
                            <i class="material-icons" aria-hidden="true">add</i>
                            <translate key="lang_add_episode">Add Episode</translate>
                        </b-button>
                    </b-card-body>

                    <data-table ref="datatable" id="station_podcast_episodes" paginated :fields="fields" :responsive="false"
                                :api-url="listUrl">
                        <template v-slot:cell(title)="row">
                            <h5 class="m-0">{{ row.item.title }}</h5>
                        </template>
                        <template v-slot:cell(podcast_media)="row">
                            <template v-if="row.item.has_media">
                                <span>{{ row.item.podcast_media.original_name }}</span>
                                <br/>
                                <small>{{ row.item.podcast_media.path }}</small>
                            </template>
                        </template>
                        <template v-slot:cell(explicit)="row">
                            <span class="badge badge-danger" v-if="row.item.explicit">
                                <translate key="explicit">Explicit</translate>
                            </span>
                        </template>
                        <template v-slot:cell(actions)="row">
                            <b-button-group size="sm">
                                <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                    <translate key="lang_btn_edit">Edit</translate>
                                </b-button>
                                <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                    <translate key="lang_btn_delete">Delete</translate>
                                </b-button>
                            </b-button-group>
                        </template>
                    </data-table>
                </b-tab>
                <b-tab :title="langAllPodcastMediaTab" no-body>
                    <podcast-media ref="podcastMedia" :listUrl="podcastMediaUrl" :station-time-zone="stationTimeZone"
                              :locale="locale" :uploadUrl="uploadUrl" :assignable-episodes-url="assignableEpisodesUrl"></podcast-media>
                </b-tab>
            </b-tabs>
        </b-card>

        <edit-modal ref="editEpisodeModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
            :locale="locale" :podcast-id="podcast.id" @relist="relist"></edit-modal>
    </div>
</template>

<script>
    import DataTable from './../Common/DataTable';
    import PodcastMedia from './Podcasts/PodcastMediaView';
    import EditModal from './Podcasts/EpisodeEditModal';
    import axios from 'axios';

    export default {
        name: 'StationPodcastEpisodes',
        components: { EditModal, PodcastMedia, DataTable },
        props: {
            listUrl: String,
            podcastMediaUrl: String,
            uploadUrl: String,
            assignableEpisodesUrl: String,
            locale: String,
            stationTimeZone: String,
            podcast: Object
        },
        data () {
            return {
                fields: [
                    { key: 'title', label: this.$gettext('Episode'), sortable: false },
                    { key: 'podcast_media', label: this.$gettext('File'), sortable: false },
                    { key: 'explicit', label: this.$gettext('Explicit'), sortable: false },
                    { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                ]
            };
        },
        computed: {
            langAllEpisodesTab () {
                return this.$gettext('All Episodes');
            },
            langAllPodcastMediaTab () {
                return this.$gettext('All Files');
            }
        },
        mounted () {
            moment.relativeTimeThreshold('ss', 1);
            moment.relativeTimeRounding(function (value) {
                return Math.round(value * 10) / 10;
            });
        },
        methods: {
            formatTime (time) {
                return moment(time).tz(this.stationTimeZone).format('LT');
            },
            formatLength (length) {
                return moment.duration(length, 'seconds').humanize();
            },
            relist () {
                if (this.$refs.datatable) {
                    this.$refs.datatable.refresh();
                }
            },
            doCreate () {
                this.$refs.editEpisodeModal.create();
            },
            doEdit (url) {
                this.$refs.editEpisodeModal.edit(url);
            },
            doDelete (url) {
                let buttonText = this.$gettext('Delete');
                let buttonConfirmText = this.$gettext('Delete episode?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#e64942',
                    showCancelButton: true,
                    focusCancel: true
                }).then((value) => {
                    if (value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

                            this.relist();
                        }).catch((err) => {
                            console.error(err);
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger');
                            }
                        });
                    }
                });
            }
        }
    };
</script>
