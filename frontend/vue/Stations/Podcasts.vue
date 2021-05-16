<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <b-row class="align-items-center">
                    <b-col md="6">
                        <h2 class="card-title" key="lang_podcasts" v-translate>Podcasts</h2>
                    </b-col>
                </b-row>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate key="lang_add_podcasts">Add Podcast</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_podcasts" paginated :fields="fields" :responsive="false"
                        :api-url="listUrl">
                <template v-slot:cell(title)="row">
                    <div>
                        <album-art class="float-right pl-3" :src="row.item.art"></album-art>

                        <h5 class="m-0">{{ row.item.title }}</h5>
                        <a :href="row.item.links.public_episodes" target="_blank">
                            <translate key="lang_link_public_page">
                            Public Page
                            </translate>
                        </a> &bull;
                        <a :href="row.item.links.public_feed" target="_blank">
                            <translate key="lang_link_rss_feed">
                            RSS Feed
                            </translate>
                        </a>

                    </div>
                </template>
                <template v-slot:cell(num_episodes)="row">
                    {{ countEpisodes(row.item.episodes) }}
                </template>
                <template v-slot:cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                        <b-button size="sm" variant="dark" :href="row.item.links.station_episodes">
                            <translate key="lang_btn_episodes">Episodes</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editPodcastModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
            :language-options="languageOptions" :categories-options="categoriesOptions" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '../Common/DataTable';
import EditModal from './Podcasts/PodcastEditModal';
import axios from 'axios';
import AlbumArt from '../Common/AlbumArt';

export default {
    name: 'StationPodcasts',
    components: { AlbumArt, EditModal, DataTable },
    props: {
        listUrl: String,
        categoriesUrl: String,
        locale: String,
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object
    },
    data () {
        return {
            fields: [
                { key: 'title', label: this.$gettext('Podcast'), sortable: false },
                { key: 'num_episodes', label: this.$gettext('# Episodes'), sortable: false },
                { key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink' }
            ]
        };
    },
    computed: {
        langAllPodcastsTab () {
            return this.$gettext('All Podcasts');
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
        countEpisodes (episodes) {
            return episodes.length;
        },
        relist () {
            if (this.$refs.datatable) {
                this.$refs.datatable.refresh();
            }
        },
        doCreate () {
            this.$refs.editPodcastModal.create();
        },
        doEdit (url) {
            this.$refs.editPodcastModal.edit(url);
        },
        doDelete (url) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete podcast?');

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
