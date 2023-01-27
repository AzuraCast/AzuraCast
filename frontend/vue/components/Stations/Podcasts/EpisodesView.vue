<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <b-row class="row align-items-center">
                <b-col md="7">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 pr-3">
                            <album-art :src="podcast.art" />
                        </div>
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ podcast.title }}
                            </h2>
                            <h4 class="card-subtitle text-muted">
                                {{ $gettext('Episodes') }}
                            </h4>
                        </div>
                    </div>
                </b-col>
                <b-col
                    md="5"
                    class="text-right text-white-50"
                >
                    <stations-common-quota
                        ref="$quota"
                        :quota-url="quotaUrl"
                    />
                </b-col>
            </b-row>
        </b-card-header>

        <b-card-body body-class="card-padding-sm">
            <div class="buttons">
                <b-button
                    variant="bg"
                    @click="doClearPodcast()"
                >
                    <icon icon="arrow_back" />
                    {{ $gettext('All Podcasts') }}
                </b-button>

                <b-button
                    variant="outline-primary"
                    @click.prevent="doCreate"
                >
                    <i
                        class="material-icons"
                        aria-hidden="true"
                    >add</i>
                    {{ $gettext('Add Episode') }}
                </b-button>
            </div>
        </b-card-body>

        <data-table
            id="station_podcast_episodes"
            ref="$datatable"
            paginated
            :fields="fields"
            :responsive="false"
            :api-url="podcast.links.episodes"
        >
            <template #cell(art)="row">
                <album-art :src="row.item.art" />
            </template>
            <template #cell(title)="row">
                <h5 class="m-0">
                    {{ row.item.title }}
                </h5>
                <a
                    :href="row.item.links.public"
                    target="_blank"
                >{{ $gettext('Public Page') }}</a>
            </template>
            <template #cell(podcast_media)="row">
                <template v-if="row.item.media">
                    <span>{{ row.item.media.original_name }}</span>
                    <br>
                    <small>{{ row.item.media.length_text }}</small>
                </template>
            </template>
            <template #cell(explicit)="row">
                <span
                    v-if="row.item.explicit"
                    class="badge badge-danger"
                >{{ $gettext('Explicit') }}</span>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </b-button>
                    <b-button
                        size="sm"
                        variant="danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </b-card>

    <edit-modal
        ref="$editEpisodeModal"
        :create-url="podcast.links.episodes"
        :station-time-zone="stationTimeZone"
        :new-art-url="podcast.links.episode_new_art"
        :new-media-url="podcast.links.episode_new_media"
        :locale="locale"
        :podcast-id="podcast.id"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './EpisodeEditModal';
import Icon from '~/components/Common/Icon';
import AlbumArt from '~/components/Common/AlbumArt';
import StationsCommonQuota from "~/components/Stations/Common/Quota";
import episodesViewProps from "~/components/Stations/Podcasts/episodesViewProps";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    ...episodesViewProps,
    podcast: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['clear-podcast']);

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pr-0'},
    {key: 'title', label: $gettext('Episode'), sortable: false},
    {key: 'podcast_media', label: $gettext('File Name'), sortable: false},
    {key: 'explicit', label: $gettext('Explicit'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $quota = ref(); // Template Ref
const $datatable = ref(); // Template Ref

const relist = () => {
    $quota.value.update();
    $datatable.value?.refresh();
};

const $editEpisodeModal = ref(); // Template Ref

const doCreate = () => {
    $editEpisodeModal.value.create();
};

const doEdit = (url) => {
    $editEpisodeModal.value.edit(url);
};

const doClearPodcast = () => {
    emit('clear-podcast');
};

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Episode?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>
