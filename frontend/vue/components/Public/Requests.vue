<template>
    <div style="overflow-x: hidden">
        <data-table
            id="song_requests"
            ref="datatable"
            paginated
            select-fields
            :page-options="pageOptions"
            :fields="fields"
            :responsive="false"
            :api-url="requestListUri"
        >
            <template #cell(name)="row">
                <div class="d-flex align-items-center">
                    <album-art
                        v-if="showAlbumArt"
                        :src="row.item.song.art"
                        :width="40"
                        class="flex-shrink-1 pr-3"
                    />
                    <div class="flex-fill">
                        {{ row.item.song.title }}<br>
                        <small>{{ row.item.song.artist }}</small>
                    </div>
                </div>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doSubmitRequest(row.item.request_url)"
                    >
                        {{ $gettext('Request') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </div>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import {forEach} from 'lodash';
import AlbumArt from '~/components/Common/AlbumArt';
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/vendor/bootstrapVue";

const props = defineProps({
    requestListUri: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    customFields: {
        type: Array,
        required: false,
        default: () => []
    }
});

const emit = defineEmits(['submitted']);

const {$gettext} = useTranslate();

const fields = computed(() => {
    let fields = [
        {
            key: 'name',
            isRowHeader: true,
            label: $gettext('Name'),
            sortable: true,
            selectable: true
        },
        {
            key: 'song.title',
            label: $gettext('Title'),
            sortable: true,
            selectable: true,
            visible: false,
        },
        {
            key: 'song.artist',
            label: $gettext('Artist'),
            sortable: true,
            selectable: true,
            visible: false,
        },
        {
            key: 'song.album',
            label: $gettext('Album'),
            sortable: true,
            selectable: true,
            visible: false
        },
        {
            key: 'song.genre',
            label: $gettext('Genre'),
            sortable: true,
            selectable: true,
            visible: false
        }
    ];

    forEach({...props.customFields}, (field) => {
        fields.push({
            key: 'song.custom_fields.' + field.short_name,
            label: field.name,
            sortable: false,
            selectable: true,
            visible: false
        });
    });

    fields.push(
        {key: 'actions', label: $gettext('Actions'), class: 'shrink', sortable: false}
    );

    return fields;
});

const pageOptions = [10, 25];

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmitRequest = (url) => {
    wrapWithLoading(
        axios.post(url)
    ).then((resp) => {
        notifySuccess(resp.data.message);
    }).finally(() => {
        emit('submitted');
    });
};
</script>

<style lang="scss">
img.album_art {
    width: 40px;
    height: auto;
    border-radius: 5px;
}
</style>
