<template>
    <section
        class="card"
        role="region"
    >
        <div class="card-header text-bg-primary">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h2 class="card-title">
                        {{ $gettext('Podcasts') }}
                    </h2>
                </div>
                <div class="col-md-5 text-end">
                    <stations-common-quota
                        ref="$quota"
                        :quota-url="quotaUrl"
                    />
                </div>
            </div>
        </div>

        <div class="card-body buttons">
            <button
                type="button"
                class="btn btn-primary"
                @click="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Podcast') }}
                </span>
            </button>
        </div>

        <data-table
            id="station_podcasts"
            ref="$datatable"
            paginated
            :fields="fields"
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
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="doSelectPodcast(row.item)"
                    >
                        {{ $gettext('Episodes') }}
                    </button>
                </div>
            </template>
        </data-table>
    </section>

    <edit-modal
        ref="$editPodcastModal"
        :create-url="listUrl"
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
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Icon from "~/components/Common/Icon.vue";
import {getStationApiUrl} from "~/router";

const props = defineProps({
    ...listViewProps,
    quotaUrl: {
        type: String,
        required: true
    }
});

const listUrl = getStationApiUrl('/podcasts');
const newArtUrl = getStationApiUrl('/podcasts/art');

const emit = defineEmits(['select-podcast']);

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pe-0'},
    {key: 'title', label: $gettext('Podcast'), sortable: false},
    {
        key: 'episodes',
        label: $gettext('# Episodes'),
        sortable: false,
        formatter: (val) => {
            return val?.length ?? 0;
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
