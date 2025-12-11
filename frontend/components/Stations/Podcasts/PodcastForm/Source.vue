<template>
    <tab
        :label="$gettext('Source')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_source"
                class="col-md-12"
                :field="r$.source"
                :options="sourceOptions"
                stacked
                radio
                :label="$gettext('Source')"
            />
        </div>

        <section
            v-show="form.source === 'playlist'"
            class="card mb-3"
            role="region"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Playlist-Based Podcast') }}
                </h2>
            </div>
            <div class="card-body">
                <p>
                    {{
                        $gettext('Playlist-based podcasts will automatically sync with the contents of a playlist, creating new podcast episodes for any media added to the playlist.')
                    }}
                </p>

                <loading :loading="playlistsLoading">
                    <div class="row g-3 mb-3">
                        <form-group-select
                            id="form_edit_playlist_id"
                            class="col-md-12"
                            :field="r$.playlist_id"
                            :options="playlistOptions"
                            :label="$gettext('Select Playlist')"
                        />

                        <form-group-checkbox
                            id="form_edit_playlist_auto_publish"
                            class="col-md-12"
                            :field="r$.playlist_auto_publish"
                            :label="$gettext('Automatically Publish New Episodes')"
                            :description="$gettext('Whether new episodes should be marked as published or held for review as unpublished.')"
                        />
                    </div>
                </loading>
            </div>
        </section>
    </tab>
</template>

<script setup lang="ts">
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {computed, onMounted, ref, shallowRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import {ApiFormSimpleOptions} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useStationsPodcastsForm} from "~/components/Stations/Podcasts/PodcastForm/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {r$, form} = storeToRefs(useStationsPodcastsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.sourceTab));

const {$gettext} = useTranslate();

const sourceOptions = [
    {
        value: 'manual',
        text: $gettext('Manually Add Episodes'),
        description: $gettext('Create podcast episodes independent of your station\'s media collection.')
    },
    {
        value: 'playlist',
        text: $gettext('Synchronize with Playlist'),
        description: $gettext('Automatically create new podcast episodes when media is added to a specified playlist.')
    }
];

const playlistsLoading = ref<boolean>(true);
const playlistOptions = shallowRef<ApiFormSimpleOptions>([]);

const {axios} = useAxios();
const {getStationApiUrl} = useApiRouter();
const playlistsApiUrl = getStationApiUrl('/podcasts/playlists');

const loadPlaylists = async () => {
    try {
        const {data} = await axios.get<ApiFormSimpleOptions>(playlistsApiUrl.value);
        playlistOptions.value = data;
    } finally {
        playlistsLoading.value = false;
    }
};

onMounted(loadPlaylists);
</script>
