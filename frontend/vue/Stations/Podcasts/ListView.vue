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
                <template #cell(art)="row">
                    <album-art :src="row.item.art"></album-art>
                </template>
                <template #cell(title)="row">
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
                </template>
                <template #cell(num_episodes)="row">
                    {{ countEpisodes(row.item.episodes) }}
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                        <b-button size="sm" variant="dark" @click.prevent="doSelectPodcast(row.item)">
                            <translate key="lang_btn_episodes">Episodes</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editPodcastModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
                    :new-art-url="newArtUrl" :language-options="languageOptions"
                    :categories-options="categoriesOptions" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '../../Common/DataTable';
import EditModal from './PodcastEditModal';
import axios from 'axios';
import AlbumArt from '../../Common/AlbumArt';
import handleAxiosError from '../../Function/handleAxiosError';

export const listViewProps = {
    props: {
        listUrl: String,
        newArtUrl: String,
        locale: String,
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object
    }
};

export default {
    name: 'ListView',
    components: { AlbumArt, EditModal, DataTable },
    mixins: [listViewProps],
    emits: ['select-podcast'],
    data () {
        return {
            fields: [
                { key: 'art', label: this.$gettext('Art'), sortable: false, class: 'shrink pr-0' },
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
        doSelectPodcast (podcast) {
            this.$emit('select-podcast', podcast);
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
                        handleAxiosError(err);
                    });
                }
            });
        }
    }
};
</script>
