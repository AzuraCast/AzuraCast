<template>
    <data-table
        id="public_requests"
        paginated
        select-fields
        :page-options="pageOptions"
        :fields="fields"
        :provider="requestListItemProvider"
    >
        <template #cell(name)="row">
            <div class="d-flex align-items-center">
                <album-art
                    v-if="showAlbumArt"
                    :src="row.item.song.art"
                    :width="40"
                    class="flex-shrink-1 pe-3"
                />
                <div class="flex-fill">
                    {{ row.item.song.title }}<br>
                    <small>{{ row.item.song.artist }}</small>
                </div>
            </div>
        </template>
        <template #cell(actions)="row">
            <button
                type="button"
                class="btn btn-sm btn-primary"
                @click="submitRequest(row.item.request_url)"
            >
                {{ $gettext('Request') }}
            </button>
        </template>
    </data-table>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {forEach} from "es-toolkit/compat";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {RequestsProps} from "~/components/Public/Requests.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {ApiStatus} from "~/entities/ApiInterfaces.ts";

const props = defineProps<RequestsProps>();

const emit = defineEmits<{
    (e: 'submitted'): void
}>();

const {$gettext} = useTranslate();

const fields = computed<DataTableField[]>(() => {
    const fields: DataTableField[] = [
        {
            key: 'name',
            isRowHeader: true,
            label: $gettext('Name'),
            sortable: false,
            selectable: true
        },
        {
            key: 'title',
            label: $gettext('Title'),
            sortable: true,
            selectable: true,
            visible: false,
            formatter: (_value, _key, item) => item.song.title
        },
        {
            key: 'artist',
            label: $gettext('Artist'),
            sortable: true,
            selectable: true,
            visible: false,
            formatter: (_value, _key, item) => item.song.artist
        },
        {
            key: 'album',
            label: $gettext('Album'),
            sortable: true,
            selectable: true,
            visible: false,
            formatter: (_value, _key, item) => item.song.album
        },
        {
            key: 'genre',
            label: $gettext('Genre'),
            sortable: true,
            selectable: true,
            visible: false,
            formatter: (_value, _key, item) => item.song.genre
        }
    ];

    forEach({...props.customFields}, (field) => {
        fields.push({
            key: 'custom_field_' + field.id,
            label: field.name,
            sortable: false,
            selectable: true,
            visible: false,
            formatter: (_value, _key, item) => item.song.custom_fields[field.short_name]
        });
    });

    fields.push(
        {
            key: 'actions',
            label: $gettext('Actions'),
            class: 'shrink',
            sortable: false
        }
    );

    return fields;
});

const requestListItemProvider = useApiItemProvider(
    props.requestListUri,
    [
        QueryKeys.PublicRequests
    ],
    {
        staleTime: 60 * 1000
    }
);

const pageOptions = [10, 25];

const {notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const doSubmitRequest = async (url: string) => {
    try {
        const {data} = await axios.post<ApiStatus>(url);

        if (data.success) {
            notifySuccess(data.message);
        } else {
            notifyError(data.message);
        }
    } finally {
        emit('submitted');
    }
};

const submitRequest = (url: string) => void doSubmitRequest(url);
</script>
