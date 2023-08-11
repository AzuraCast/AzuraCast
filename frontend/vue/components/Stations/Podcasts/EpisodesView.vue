<template>
    <section
        class="card"
        role="region"
    >
        <div class="card-header text-bg-primary">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 pe-3">
                            <album-art :src="podcast.art" />
                        </div>
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ podcast.title }}
                            </h2>
                            <h4 class="card-subtitle">
                                {{ $gettext('Episodes') }}
                            </h4>
                        </div>
                    </div>
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
                class="btn btn-secondary"
                @click="doClearPodcast()"
            >
                <icon icon="arrow_back" />
                <span>
                    {{ $gettext('All Podcasts') }}
                </span>
            </button>
            <button
                type="button"
                class="btn btn-primary"
                @click="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Episode') }}
                </span>
            </button>
        </div>

        <data-table
            id="station_podcast_episodes"
            ref="$datatable"
            paginated
            :fields="fields"
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
                    class="badge text-bg-danger"
                >{{ $gettext('Explicit') }}</span>
                <span v-else>&nbsp;</span>
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
                </div>
            </template>
        </data-table>
    </section>

    <edit-modal
        ref="$editEpisodeModal"
        :create-url="podcast.links.episodes"
        :new-art-url="podcast.links.episode_new_art"
        :new-media-url="podcast.links.episode_new_media"
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
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    quotaUrl: {
        type: String,
        required: true
    },
    podcast: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['clear-podcast']);

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pe-0'},
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
