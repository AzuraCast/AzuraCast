<template>
    <tab
        :label="$gettext('Source')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_source"
                class="col-md-12"
                :field="v$.source"
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
                            :field="v$.playlist_id"
                            :options="playlistOptions"
                            :label="$gettext('Select Playlist')"
                        />

                        <form-group-checkbox
                            id="form_edit_playlist_auto_publish"
                            class="col-md-12"
                            :field="v$.playlist_auto_publish"
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
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {onMounted, ref, shallowRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import {getStationApiUrl} from "~/router.ts";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps<FormTabProps>();
const emit = defineEmits<FormTabEmits>();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        source: {required},
        playlist_id: {},
        playlist_auto_publish: {}
    },
    {
        source: 'manual',
        playlist_id: null,
        playlist_auto_publish: true,
    }
);

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

const playlistsLoading = ref(true);
const playlistOptions = shallowRef([]);

const {axios} = useAxios();
const playlistsApiUrl = getStationApiUrl('/podcasts/playlists');

const loadPlaylists = () => {
    axios.get(playlistsApiUrl.value).then((resp) => {
        playlistOptions.value = resp.data;
    }).finally(() => {
        playlistsLoading.value = false;
    });
};

onMounted(loadPlaylists);
</script>
