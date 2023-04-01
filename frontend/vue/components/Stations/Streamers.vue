<template>
    <div class="row row-of-cards">
        <div class="col-md-8">
            <section
                class="card"
                role="region"
                aria-labelledby="hdr_streamer_accounts"
            >
                <b-card-header header-bg-variant="primary-dark">
                    <b-row class="align-items-center">
                        <b-col md="6">
                            <h2
                                id="hdr_streamer_accounts"
                                class="card-title"
                            >
                                {{ $gettext('Streamer/DJ Accounts') }}
                            </h2>
                        </b-col>
                        <b-col
                            md="6"
                            class="text-right text-muted"
                        >
                            {{
                                $gettext(
                                    'This station\'s time zone is currently %{tz}.',
                                    {tz: stationTimeZone}
                                )
                            }}
                        </b-col>
                    </b-row>
                </b-card-header>

                <b-tabs
                    pills
                    card
                    lazy
                >
                    <b-tab
                        :title="$gettext('Account List')"
                        no-body
                    >
                        <b-card-body body-class="card-padding-sm">
                            <b-button
                                variant="outline-primary"
                                @click.prevent="doCreate"
                            >
                                <icon icon="add" />
                                {{ $gettext('Add Streamer') }}
                            </b-button>
                        </b-card-body>

                        <data-table
                            id="station_streamers"
                            ref="$datatable"
                            :fields="fields"
                            :api-url="listUrl"
                        >
                            <template #cell(art)="row">
                                <album-art :src="row.item.art" />
                            </template>
                            <template #cell(streamer_username)="row">
                                <code>{{ row.item.streamer_username }}</code>
                                <div>
                                    <span
                                        v-if="!row.item.is_active"
                                        class="badge badge-danger"
                                    >
                                        {{ $gettext('Disabled') }}
                                    </span>
                                </div>
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
                                        variant="default"
                                        @click.prevent="doShowBroadcasts(row.item.links.broadcasts)"
                                    >
                                        {{ $gettext('Broadcasts') }}
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
                    </b-tab>
                    <b-tab
                        :title="$gettext('Schedule View')"
                        no-body
                    >
                        <schedule
                            ref="$schedule"
                            :schedule-url="scheduleUrl"
                            :station-time-zone="stationTimeZone"
                            @click="doCalendarClick"
                        />
                    </b-tab>
                </b-tabs>
            </section>
        </div>
        <div class="col-md-4">
            <connection-info :connection-info="connectionInfo" />
        </div>

        <edit-modal
            ref="$editModal"
            :create-url="listUrl"
            :station-time-zone="stationTimeZone"
            :new-art-url="newArtUrl"
            @relist="relist"
        />
        <broadcasts-modal ref="$broadcastsModal" />
    </div>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Streamers/EditModal';
import BroadcastsModal from './Streamers/BroadcastsModal';
import Schedule from '~/components/Common/ScheduleView';
import Icon from '~/components/Common/Icon';
import ConnectionInfo from "./Streamers/ConnectionInfo";
import AlbumArt from "~/components/Common/AlbumArt";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    },
    scheduleUrl: {
        type: String,
        required: true
    },
    stationTimeZone: {
        type: String,
        required: true
    },
    connectionInfo: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'art', label: $gettext('Art'), sortable: false, class: 'shrink pr-0'},
    {key: 'display_name', label: $gettext('Display Name'), sortable: true},
    {key: 'streamer_username', isRowHeader: true, label: $gettext('Username'), sortable: true},
    {key: 'comments', label: $gettext('Notes'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const doCalendarClick = (event) => {
    doEdit(event.extendedProps.edit_url);
};

const $broadcastsModal = ref(); // Template Ref

const doShowBroadcasts = (url) => {
    $broadcastsModal.value.open(url);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Streamer?'),
    relist
);
</script>
