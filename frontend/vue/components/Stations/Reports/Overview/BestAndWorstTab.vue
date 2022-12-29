<template>
    <b-overlay
        variant="card"
        :show="loading"
    >
        <div
            v-if="loading"
            class="card-body py-5"
        >
            &nbsp;
        </div>
        <div
            v-else
            class="card-body"
        >
            <b-row>
                <b-col
                    md="6"
                    class="mb-4"
                >
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
                                <tr v-for="row in bestAndWorst.best">
                                    <td class="text-center text-success">
                                        <icon icon="keyboard_arrow_up" />
                                        {{ row.stat_delta }}
                                        <br>
                                        <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                    </td>
                                    <td>
                                        <span v-html="getSongText(row.song)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                </b-col>
                <b-col
                    md="6"
                    class="mb-4"
                >
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
                                <tr v-for="row in bestAndWorst.worst">
                                    <td class="text-center text-danger">
                                        <icon icon="keyboard_arrow_down" />
                                        {{ row.stat_delta }}
                                        <br>
                                        <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                    </td>
                                    <td>
                                        <span v-html="getSongText(row.song)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                </b-col>

                <b-col
                    md="12"
                    class="mb-4"
                >
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
                                <tr v-for="row in mostPlayed">
                                    <td class="text-center">
                                        {{ row.num_plays }}
                                    </td>
                                    <td>
                                        <span v-html="getSongText(row.song)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                </b-col>
            </b-row>
        </div>
    </b-overlay>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import {useMounted} from "@vueuse/core";
import {onMounted, ref, shallowRef, toRef, watch} from "vue";
import {DateTime} from "luxon";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    dateRange: Object,
    apiUrl: String,
});

const loading = ref(true);
const bestAndWorst = shallowRef({
    best: [],
    worst: []
});
const mostPlayed = ref([]);

const dateRange = toRef(props, 'dateRange');
const {axios} = useAxios();

const relist = () => {
    loading.value = true;

    axios.get(props.apiUrl, {
        params: {
            start: DateTime.fromJSDate(dateRange.value.startDate).toISO(),
            end: DateTime.fromJSDate(dateRange.value.endDate).toISO()
        }
    }).then((response) => {
        bestAndWorst.value = response.data.bestAndWorst;
        mostPlayed.value = response.data.mostPlayed;
        loading.value = false;
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

const getSongText = (song) => {
    if (song.title !== '') {
        return '<b>' + song.title + '</b><br>' + song.artist;
    }

    return song.text;
};
</script>
