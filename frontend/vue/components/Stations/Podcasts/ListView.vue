<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <b-row class="align-items-center">
                <b-col md="7">
                    <h2 class="card-title">
                        {{ $gettext('Podcasts') }}
                    </h2>
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
                    variant="outline-primary"
                    @click.prevent="doCreate"
                >
                    <i
                        class="material-icons"
                        aria-hidden="true"
                    >add</i>
                    {{ $gettext('Add Podcast') }}
                </b-button>
            </div>
        </b-card-body>

        <data-table
            id="station_podcasts"
            ref="$datatable"
            paginated
            :fields="fields"
            :responsive="false"
            :api-url="listUrl"
        >
            <template #cell(art)="row">
                <album-art :src="row.item.art" />
            </template>
            <template #cell(title)="row">
                <h5 class="m-0">
                    {{ row.item.title }}
                </h5>
                <a
                    :href="row.item.links.public_episodes"
                    target="_blank"
                >{{ $gettext('Public Page') }}</a> &bull;
                <a
                    :href="row.item.links.public_feed"
                    target="_blank"
                >{{ $gettext('RSS Feed') }}</a>
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
                    <b-button
                        size="sm"
                        variant="dark"
                        @click.prevent="doSelectPodcast(row.item)"
                    >
                        {{ $gettext('Episodes') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </b-card>

    <edit-modal
        ref="$editPodcastModal"
        :create-url="listUrl"
        :station-time-zone="stationTimeZone"
        :new-art-url="newArtUrl"
        :language-options="languageOptions"
        :categories-options="categoriesOptions"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './PodcastEditModal';
import AlbumArt from '~/components/Common/AlbumArt';
import StationsCommonQuota from "~/components/Stations/Common/Quota";
import listViewProps from "./listViewProps";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    ...listViewProps
});

const emit = defineEmits(['select-podcast']);

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pr-0'},
    {key: 'title', label: $gettext('Podcast'), sortable: false},
    {
        key: 'episodes',
        label: $gettext('# Episodes'),
        sortable: false,
        formatter: (val) => {
            return val.length;
        }
    },
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $quota = ref(); // Template Ref
const $datatable = ref(); // Template Ref

const relist = () => {
    $quota.value.update();
    $datatable.value?.refresh();
};

const $editPodcastModal = ref(); // Template Ref

const doCreate = () => {
    $editPodcastModal.value.create();
};

const doEdit = (url) => {
    $editPodcastModal.value.edit(url);
};

const doSelectPodcast = (podcast) => {
    emit('select-podcast', podcast);
};

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Podcast?'),
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
