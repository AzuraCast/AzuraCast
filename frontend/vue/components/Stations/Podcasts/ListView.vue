<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <b-row class="align-items-center">
                    <b-col md="7">
                        <h2 class="card-title" key="lang_podcasts" v-translate>Podcasts</h2>
                    </b-col>
                    <b-col md="5" class="text-right text-white-50">
                        <stations-common-quota :quota-url="quotaUrl" ref="quota"></stations-common-quota>
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
import DataTable from '~/components/Common/DataTable';
import EditModal from './PodcastEditModal';
import AlbumArt from '~/components/Common/AlbumArt';
import StationsCommonQuota from "~/components/Stations/Common/Quota";

export const listViewProps = {
    props: {
        listUrl: String,
        newArtUrl: String,
        quotaUrl: String,
        locale: String,
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object
    }
};

export default {
    name: 'ListView',
    components: {StationsCommonQuota, AlbumArt, EditModal, DataTable},
    mixins: [listViewProps],
    emits: ['select-podcast'],
    data() {
        return {
            fields: [
                {key: 'art', label: this.$gettext('Art'), sortable: false, class: 'shrink pr-0'},
                {key: 'title', label: this.$gettext('Podcast'), sortable: false},
                {key: 'num_episodes', label: this.$gettext('# Episodes'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    computed: {
        langAllPodcastsTab () {
            return this.$gettext('All Podcasts');
        }
    },
    methods: {
        countEpisodes (episodes) {
            return episodes.length;
        },
        relist () {
            this.$refs.quota.update();
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
            this.$confirmDelete({
                title: this.$gettext('Delete Podcast?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        }
    }
};
</script>
