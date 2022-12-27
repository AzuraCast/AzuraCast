<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <b-row class="align-items-center">
                <b-col md="7">
                    <h2 class="card-title">{{ $gettext('Podcasts') }}</h2>
                </b-col>
                <b-col md="5" class="text-right text-white-50">
                    <stations-common-quota :quota-url="quotaUrl" ref="quota"></stations-common-quota>
                </b-col>
            </b-row>
        </b-card-header>

        <b-card-body body-class="card-padding-sm">
            <div class="buttons">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    {{ $gettext('Add Podcast') }}
                </b-button>
            </div>
        </b-card-body>

        <data-table ref="datatable" id="station_podcasts" paginated :fields="fields" :responsive="false"
                    :api-url="listUrl">
            <template #cell(art)="row">
                <album-art :src="row.item.art"></album-art>
            </template>
            <template #cell(title)="row">
                <h5 class="m-0">{{ row.item.title }}</h5>
                <a :href="row.item.links.public_episodes" target="_blank">{{ $gettext('Public Page') }}</a> &bull;
                <a :href="row.item.links.public_feed" target="_blank">{{ $gettext('RSS Feed') }}</a>
            </template>
            <template #cell(num_episodes)="row">
                {{ countEpisodes(row.item.episodes) }}
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                        {{ $gettext('Edit') }}
                    </b-button>
                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                        {{ $gettext('Delete') }}
                    </b-button>
                    <b-button size="sm" variant="dark" @click.prevent="doSelectPodcast(row.item)">
                        {{ $gettext('Episodes') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </b-card>

    <edit-modal ref="editPodcastModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
                :new-art-url="newArtUrl" :language-options="languageOptions"
                :categories-options="categoriesOptions" @relist="relist"></edit-modal>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './PodcastEditModal';
import AlbumArt from '~/components/Common/AlbumArt';
import StationsCommonQuota from "~/components/Stations/Common/Quota";
import listViewProps from "./listViewProps";

export default {
    name: 'ListView',
    components: {StationsCommonQuota, AlbumArt, EditModal, DataTable},
    props: {
        ...listViewProps
    },
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
