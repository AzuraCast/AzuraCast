<template>
    <loading
        :loading="isLoading"
        lazy
    >
        <div class="row">
            <div class="col-md-6 mb-4">
                <fieldset>
                    <legend>
                        {{ $gettext('Best Performing Songs') }}
                    </legend>

                    <table class="table table-striped table-condensed table-nopadding">
                        <colgroup>
                            <col width="20%">
                            <col width="80%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>
                                    {{ $gettext('Change') }}
                                </th>
                                <th>
                                    {{ $gettext('Song') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in bestAndWorst.best"
                                :key="row.song.id"
                            >
                                <td class=" text-center text-success">
                                    <icon icon="keyboard_arrow_up" />
                                    {{ row.stat_delta }}
                                    <br>
                                    <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                </td>
                                <td>
                                    <song-text :song="row.song" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </div>
            <div class="col-md-6 mb-4">
                <fieldset>
                    <legend>
                        {{ $gettext('Worst Performing Songs') }}
                    </legend>

                    <table class="table table-striped table-condensed table-nopadding">
                        <colgroup>
                            <col width="20%">
                            <col width="80%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>
                                    {{ $gettext('Change') }}
                                </th>
                                <th>
                                    {{ $gettext('Song') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in bestAndWorst.worst"
                                :key="row.song.id"
                            >
                                <td class="text-center text-danger">
                                    <icon icon="keyboard_arrow_down" />
                                    {{ row.stat_delta }}
                                    <br>
                                    <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                </td>
                                <td>
                                    <song-text :song="row.song" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </div>
            <div class="col-md-12 mb-4">
                <fieldset>
                    <legend>
                        {{ $gettext('Most Played Songs') }}
                    </legend>

                    <table class="table table-striped table-condensed table-nopadding">
                        <colgroup>
                            <col width="10%">
                            <col width="90%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>
                                    {{ $gettext('Plays') }}
                                </th>
                                <th>
                                    {{ $gettext('Song') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in mostPlayed"
                                :key="row.song.id"
                            >
                                <td class="text-center">
                                    {{ row.num_plays }}
                                </td>
                                <td>
                                    <song-text :song="row.song" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </div>
        </div>
    </loading>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import {useMounted} from "@vueuse/core";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import SongText from "~/components/Stations/Reports/Overview/SongText.vue";
import Loading from "~/components/Common/Loading.vue";
import {useLuxon} from "~/vendor/luxon";

const props = defineProps({
    dateRange: {
        type: Object,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    },
});

const isLoading = ref(true);
const bestAndWorst = shallowRef({
    best: [],
    worst: []
});
const mostPlayed = ref([]);

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const {DateTime} = useLuxon();

const relist = () => {
    isLoading.value = true;

    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then((response) => {
        bestAndWorst.value = response.data.bestAndWorst;
        mostPlayed.value = response.data.mostPlayed;
        isLoading.value = false;
    });
};

const isMounted = useMounted();

watch(dateRange, () => {
    if (isMounted.value) {
        relist();
    }
});

onMounted(() => {
    relist();
});
</script>
