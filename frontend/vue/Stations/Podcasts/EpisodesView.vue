<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark" class="d-flex align-items-center">
                <div class="flex-shrink-0 pr-3">
                    <album-art :src="podcast.art"></album-art>
                </div>
                <div class="flex-fill">
                    <h2 class="card-title">{{ podcast.title }}</h2>
                    <h4 class="card-subtitle text-muted" key="lang_episodes" v-translate>Episodes</h4>
                </div>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="bg" @click="doClearPodcast()">
                    <icon icon="arrow_back"></icon>
                    <translate key="lang_podcast_back">All Podcasts</translate>
                </b-button>

                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate key="lang_add_episode">Add Episode</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_podcast_episodes" paginated :fields="fields" :responsive="false"
                        :api-url="podcast.links.episodes">
                <template #cell(art)="row">
                    <album-art :src="row.item.art"></album-art>
                </template>
                <template #cell(title)="row">
                    <h5 class="m-0">{{ row.item.title }}</h5>
                    <a :href="row.item.links.public" target="_blank">
                        <translate key="lang_link_public">
                        Public Page
                        </translate>
                    </a>
                </template>
                <template #cell(podcast_media)="row">
                    <template v-if="row.item.media">
                        <span>{{ row.item.media.original_name }}</span>
                        <br/>
                        <small>{{ row.item.media.length_text }}</small>
                    </template>
                </template>
                <template #cell(explicit)="row">
                        <span class="badge badge-danger" v-if="row.item.explicit">
                            <translate key="explicit">Explicit</translate>
                        </span>
                </template>
                <template #cell(actions)="row">
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
        </b-card>

        <edit-modal ref="editEpisodeModal" :create-url="podcast.links.episodes" :station-time-zone="stationTimeZone"
                    :new-art-url="podcast.links.episode_new_art" :new-media-url="podcast.links.episode_new_media"
                    :locale="locale" :podcast-id="podcast.id" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from './../../Common/DataTable';
import EditModal from './EpisodeEditModal';
import axios from 'axios';
import Icon from '../../Common/Icon';
import AlbumArt from '../../Common/AlbumArt';
import EpisodeFormBasicInfo from './EpisodeForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import handleAxiosError from '../../Function/handleAxiosError';

export const episodeViewProps = {
    props: {
        locale: String,
        stationTimeZone: String
    }
};

export default {
    name: 'EpisodesView',
    components: { PodcastCommonArtwork, EpisodeFormBasicInfo, AlbumArt, Icon, EditModal, DataTable },
    mixins: [episodeViewProps],
    props: {
        podcast: Object
    },
    emits: ['clear-podcast'],
    data () {
        return {
            fields: [
                { key: 'art', label: this.$gettext('Art'), sortable: false, class: 'shrink pr-0' },
                { key: 'title', label: this.$gettext('Episode'), sortable: false },
                { key: 'podcast_media', label: this.$gettext('File'), sortable: false },
                { key: 'explicit', label: this.$gettext('Explicit'), sortable: false },
                { key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink' }
            ]
        };
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
        doClearPodcast () {
            this.$emit('clear-podcast');
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
